<?php
/**
 * Created by PhpStorm.
 * User: marin
 * Date: 2017/5/13
 * Time: 23:31
 */

/**
 *
 */
namespace net\client\cserver;


class CServer
{
    protected $_config = null;

    /**
     * 加入内存锁
     * @var $_lock
     */
    protected $_lock = null;

    /**
     * 使用内存数据结构channel 用于缓存server消息
     * @var null
     */
    protected $_cacheChannel = null;
    protected $_cacheSize = 33554432;//1024 * 1024 * 32 32mb

    public function __construct(\core\ArrayToObject $config = null)
    {
        if (!$config) {
            $config = \core\Config::getConf('obj')->CServer;
        }
        $this->_config = $config;
        $this->_cacheChannel = new \Swoole\Channel($this->_cacheSize);
        $this->_lock = $lock = new \swoole_lock(SWOOLE_MUTEX);//互斥锁,(channel底层基于共享内存+Mutex互斥锁实现，可实现用户态的高性能内存队列)
    }

}