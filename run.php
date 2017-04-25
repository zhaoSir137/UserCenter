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

//todo mysql连接池

//todo mysqlLog 连接池

//todo MysqlUserCenter 连接池

//todo redis连接