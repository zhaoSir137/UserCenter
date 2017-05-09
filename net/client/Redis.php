<?php
namespace net\client;
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

    public function __construct(\core\ArrayToObject $config=null)
    {
        if(!$config){
            $this ->_config = \core\Config::getConf('obj')->Redis;
        }
        $this ->_config = $config;
    }
}