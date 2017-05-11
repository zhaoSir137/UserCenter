<?php
/**
 * Created by PhpStorm.
 * User: marin
 * Date: 2017/4/27
 * Time: 22:47
 */

namespace net\client;


/**
 * mysql 线程池
 * Class Mysql
 * @package core\client
 */
class Mysql
{
    protected $_size = 5;
    protected $_maxSize = 20;
    protected $_config = [];

    protected $_startCallBack = null;

    /** 存放mysql线程的一个池子
     * @var
     */
    protected $_pool = [];

    /**
     * 空线的池子，存放空闲的链接池
     * @var array
     */
    protected $_free = [];

    /**
     * @var array 查询队列
     */
    protected $_queryQueue = [];


    public function __construct(\core\Config $config=null)
    {
        if(!$config){
            $config = \core\Config::getConf('obj')->Mysql;
        }
        $this ->_config = $config;
        $this ->_size = $config->pool->init;
        $this ->_maxSize = $config->pool->max;
    }

    public function start($callback=null)
    {
        $config = $this ->_config->toArray();
        if(isset($config['pool'])) {
            unset($config['pool']);
        }
        if($callback){
            $this ->_startCallBack = $callback;
        }
        $uuid = strval(microtime(true));
        $closeCallBack = function ($db){
            \core\Log::CreateNew()->printLn("MySQL connection is closed：{$db->uuid}");
        };
        for ($i=count($this->_pool) ; $i<$this->_size;$i++){
            $db = new \swoole_mysql();
            $db ->uuid = "{$uuid}:{$i}";
            $this ->_pool[$db->uuid] = $db;
            $db->on('Close',function ($db)use($closeCallBack){
                $this ->recycle($db,$closeCallBack);
            });
            $this ->mysqlConnect($db,$config);
        }
    }

    public function mysqlConnect($db,$config)
    {
        $db ->connect($config,function ($db,$result){
            if($result === false){
                \core\Log::CreateNew()->printLn("Mysql Connecte Error:{$db->uuid}");
                return;
            }
            \core\Log::CreateNew()->printLn("Mysql Connect Success :{$db->uuid}");
            if($this->_startCallBack){
                $callback = $this->_startCallBack;
                $this->_startCallBack =null;
                call_user_func($callback,$this);
            }
            $this ->_free[$db->uuid] = $db;
        });
    }

    /**
     * 回收断开连接了的mysql连接对象
     * @param $db
     */
    public function recycle($db,$callback=null)
    {
        $uuid = $db->uuid;
        if(isset($this->_pool[$db->uuid])){
            unset($this->_pool[$db->uuid]);
        }
        if(isset($this->_free[$db->uuid])){
            unset($this->_free[$db->uuid]);
        }
        if($callback){
            call_user_func($callback,$db,$uuid);
        }
        unset($db);
        $this ->start();
    }

    /**
     * @param $sql
     * @param $callback
     * @param null $db
     */
    public function query($sql,$callback,$db=null)
    {
        if(!$db){
            $db = $this ->getFreeMysql();
        }
        if(!$db && count($this->_queryQueue) < 1000)
        {
            \core\Log::CreateNew()->printLn('Mysql Cache Query:'.$sql);
            $this->_queryQueue[]=[$sql,$callback];
        }elseif ($db){
            \core\Log::CreateNew()->printLn("Mysql Query:{$db->uuid}:sql:{$sql}");
            $db->query($sql,function ($db,$result)use($sql,$callback){
                if($result === false && ($db->errno==2013 || $db->errno==2013)){
                    $this->query($sql,$callback);
                    \core\Log::CreateNew()->printLn('Mysql Query Error Recycle:'.$db->uuid);
                    $this ->recycle($db);
                }else{
                    call_user_func($callback,$db,$result);
                    $queue = array_shift($this->_queryQueue);
                    if($queue){
                        \core\Log::New()->println("Mysql Query Queue:{$db->uuid}");
                        $this->query($queue[0],$queue[1],$db);
                        $this->start();//
                    }else{
                        \core\Log::CreateNew()->printLn("Mysql Free:{$db->uuid}");
                        $this ->_free[$db->uuid]=$db;//放回空闲池中
                    }
                }
            });
        }


    }

    public function getFreeMysql()
    {
        $db=array_shift($this->_free);
        if(!$db){
            $this->_size = min($this->_size+5,$this->_maxSize);//如果线程池没有了
        }
        return $db;
    }

    /**
     * @param $table
     * @param $data
     * @param null $callback
     */
    public function insert($table,$data,$callback=null)
    {
        $db = $this ->_pool[array_rand($this->_pool)];
        if(!$db){
            call_user_func($callback,false,false);return;
        }
        if(!$callback){
            $callback = function (){};
        }
        $field = array_keys($data);
        $field = implode('`,`',$field);
        $data = $this ->escape($db,$data);
        $value = implode(',',$data);
        $sql = "INSERT  INTO {$table} ($field) VALUES ({$value})";
        $this ->query($sql,function ($db,$r)use($callback){
            if(!$r){
                call_user_func($callback,false);//插入数据库失败,直接返回失败,或许需要回调传db
            }else{
                $return = $db->insert_id?$db->insert_id:true;
                call_user_func($callback,$return);
            }
        });
    }


    /**
     * 转义data中的特殊字符，避免SQL注入攻击
     * 该方法依赖mysqlnd，切必须在Mysql连接完成之后才能使用
     */
    public function escape($db,$data)
    {
        if(method_exists($db,'escape')){
            if(is_array($data)){
                foreach ($data as $field =>$value){
                    if(is_string($value)){
                        $value = $db ->escape($value);
                        $data[$field] = $value;
                    }
                }
            }elseif (is_string($data)){
                $data = $db ->escape($data);
            }

        }else{
            foreach ($data as $field => $val){
                $data[$field] = "'{$val}'";
            }
        }

        return $data;
    }

    /**
     * 修改
     * @param $table
     * @param $data
     * @param $where
     * @param $callback
     */
    public function update($table,$data,$where,$callback)
    {
        $db = $this->_pool[array_rand($this->_pool)];
        if(!$db){
            call_user_func($callback,false);return;
        }
        $data = $this ->escape($db,$data);
        $sql = "UPDATE {$table} SET @keyVal@";

        if(is_array($data)){
            $keyVal = '';
            foreach ($data as $field => $v){
                $keyVal .= "`{$field}`=`{$v}`,";
            }
            $keyVal = trim($keyVal,',');
            $sql = str_replace('@keyVal@',$keyVal,$sql);

            $this ->query($sql,function ($db,$result)use($callback){
                if($result){
                    call_user_func($callback,$db->affected_rows);
                }else{
                    call_user_func($callback,false);
                }
            });
        }else{//必须是一个数组，否则直接返回错误
            call_user_func($callback,-1);
        }

    }

}