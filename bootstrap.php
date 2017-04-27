<?php

date_default_timezone_set('Asia/Shanghai');
define('DS', DIRECTORY_SEPARATOR);
define('CONF_DIR',realpath(__DIR__.DS.'Config'.DS));
define('RES_DIR', realpath(__DIR__.'/result/').DS);
define('RES_LOG_DIR', RES_DIR.'log'.DS);

/**
 * 注册自动加载函数
 */
spl_autoload_register(function($class_name){
	if(!class_exists($class_name)){
		$file_name = trim($class_name,'\\');
		$file_name = str_replace('\\', DS, $file_name);
		$file_name = __DIR__.DS.$file_name.'.php';
		if(is_file($file_name)){
			include $file_name;
		}
	}
	return class_exists($class_name);
});