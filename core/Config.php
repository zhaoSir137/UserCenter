<?php
/**
 * Created by PhpStorm.
 * User: marin
 * Date: 2017/4/27
 * Time: 22:53
 */

namespace core;

/**
 * Class Config
 * 配置
 * @package core
 */
class Config
{
    public static function getConf($type='array')
    {
        $returnConfig = null;
        if($returnConfig==null){
            $jsonStr = file_get_contents(CONF_DIR.'config.json');
            $config = json_decode($jsonStr,true);
            $returnConfig = $config?$config:array();
            if($type == 'array'){
                return $returnConfig;
            }elseif('obj'){
                $returnConfig = new ArrayToObject($returnConfig);
            }
        }
        return $returnConfig;
    }
}