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


}