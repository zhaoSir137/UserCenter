<?php
/**
 * Created by PhpStorm.
 * User: marin
 * Date: 2017/4/26
 * Time: 22:55
 */

namespace core;
class Log
{
    protected  $_fs = null;
    protected  $_tickt = null;
    public function __construct()
    {
        //每1分钟检查一次是否允许写日志
        $this ->_tickt = swoole_timer_tick(6000,[$this,'Detection']);
        $this ->Detection();
    }

    public function __destruct()
    {
        if(is_resource($this->_fs)){
            fclose($this->_fs);
        }
    }

    /**
     * 侦测文件是否可写
     */
    public function Detection()
    {
        if(file_exists(RES_LOG_DIR.'/log.lock') && $this->_fs && !is_writeable(RES_LOG_DIR.'/log.txt')){//文件不可写，停止写日志
            fclose($this->_fs);
            $this ->_fs = null;
        }else if (!file_exists(RES_LOG_DIR.'/log.lock') && !$this->_fs){//文件可写
            $this ->_fs = @fopen(RES_LOG_DIR.'/log.txt','w');
        }
    }

    public function printLn($logInfo)
    {
        if($this->_fs){
            fwrite($this->_fs, date('Y-m-d H:i:s')."\t\t".$logInfo."\n");
        }
    }


    public static function CreateNew()
    {
        static $self=null;
        if(is_null($self)){
            $self = new  self();
        }
        return $self;
    }
}