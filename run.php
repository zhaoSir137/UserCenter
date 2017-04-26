<?php
/**
 * 使用异步管理器 管理异步回调
 * 将异步任务回调加入到管理器中
 *
 * 全部回调执行完了之后(比如 数据库链接池，redis池，)，执行入口，链接server，使用swoole异步客户端连接TCPserver
 * 数据库链接成功，redis链接成功 之后 加入到CServer管理器，CSserver主要用作消息接收，根据消息处理相应的模块业务
 */
include __DIR__."/bootstrap.php";
ini_set('memory_limit','1024M');
$async = new \core\asyncTaskManager();
//todo mysql连接池
$async ->push('mysql',function(){
    \core\Log::CreateNew()->printLn('Mysql Successful');
});
//todo mysqlLog 连接池
$async ->push('mysqlLog',function(){
    \core\Log::CreateNew()->printLn('MysqlLog Successful');
});
//todo MysqlUserCenter 连接池
$async ->push('mysqlUcenter',function (){
    \core\Log::CreateNew()->printLn('Mysql User Center DB Successful');
});
//todo redis连接
$async ->push('redis',function(){
    \core\Log::CreateNew()->printLn('Redis Connecte Successful');
});

$async ->onComplete(function(){
    //todo 启动服务
});