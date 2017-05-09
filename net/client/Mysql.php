<?php
/**
 * Created by PhpStorm.
 * User: marin
 * Date: 2017/4/27
 * Time: 22:47
 */

namespace core\client;


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

}