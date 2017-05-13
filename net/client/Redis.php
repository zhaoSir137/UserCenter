<?php
namespace net\client;
use net\Relink;

/**
 * Created by PhpStorm.
 * User: marin
 * Date: 2017/5/9
 * Time: 23:07
 */
class Redis{
    protected $_config = null;
    protected $_redis = [];
    protected $_available = [];
    protected $_startCallback = null;

    /** @var Relink $_ReLink  */
    protected $_ReLink = null;

    public function __construct(\core\ArrayToObject $config=null)
    {
        if(!$config){
            $this ->_config = \core\Config::getConf('obj')->Redis;
        }
        $this ->_config = $config;
    }



    public function start(callable $startCallBack=null)
    {
        $size = $this ->_config->pool;
        $uuid = strval(microtime(true));
        if ($startCallBack){
            $this ->_startCallback = $startCallBack;
        }
        for ($i=count($this->_redis);$i<$size;$i++){
            $redis = new \swoole_redis();
            $redis ->uuid ="{$uuid}:{$i}";
            $redis ->on('close',[$this,"onClose"]);
            $redis ->reLink = new \net\Relink($redis,[$this,'onReLink']);
            $this->_redis[$redis->uuid] = $redis;
            $this->onReLink($redis);
        }
    }

    public function onReLink($redis)
    {
        $redis->connect($this->_config->host,$this->_config->port,[$this,'onConnect']);
    }

    public function onConnect($redis,$result)
    {
        if(!$result){
            \core\Log::CreateNew()->printLn("Redis Connect Error:{$redis->uuid}");
            $this->relink($redis);
        }
        if($this->_config->pwd){//如果redis有密码验证需要
            $redis->auth($this->_config->pwd,function ($client,$result){
                if($result == 'OK'){
                    //存入到可用的redis集合中
                    $this ->_available[$client->uuid] = $client;
                    \core\Log::CreateNew()->printLn("Redis Connect Successful:{$client->uuid}");
                    if($this->_startCallback){
                        $startCallBack = $this->_startCallback;$this->_startCallback=null;
                        call_user_func($startCallBack,$this);
                    }
                }else{
                    $this ->freeRedis($client);return;
                }
            });
        }else{
            $this->_available[$redis->uuid] =$redis;
            \core\Log::CreateNew()->printLn("Redis Connect Successful:{$redis->uuid}");
            if($this->_startCallback){
                $startCallBack = $this->_startCallback;
                $this ->_startCallback = null;
                call_user_func($startCallBack,$redis);
            }
        }

    }

    public function relink($redis)
    {
        if(isset($this->_available[$redis->uuid])){
            unset($this->_available[$redis->uuid]);
        }

        if(!$redis->reLink->OnReLink()){//重试次数已经大于最大重试次数 不在继续尝试重连
            //释放Redis
            $this->freeRedis($redis);
            //调用start 重新生成RedisClient
            $this->start();
        }

    }

    /**
     * 释放有问题的redis 比如说密码未验证通过的 或者 重连机制尝试了 但是仍然未连接成功的
     * @param $redis
     */
    public function freeRedis($redis)
    {
        //释放可用的redis
        if(isset($this->_available[$redis->uuid])){
            unset($this->_available[$redis->uuid]);
        }
        //释放线程池里边的redis
        if(isset($this->_redis[$redis->uuid])){
            unset($this->_redis[$redis->uuid]);
        }

        \core\Log::CreateNew()->printLn("Redis FreeRedis :{$redis->uuid}");
    }

    /**
     * redis 断开连接之后的重连机制
     * @param \swoole_redis $redis
     */
    public function onClose(\swoole_redis $redis)
    {
        \core\Log::CreateNew()->printLn("Redis Close:{$redis->uuid}");
        $this ->relink($redis);
    }

    /** 获取可用的redis
     * @return bool|mixed
     */
    public function getAvailableRedis()
    {
        if(empty($this->_available)){
            return false;
        }
        return $this->_available[array_rand($this->_available)];
    }

}