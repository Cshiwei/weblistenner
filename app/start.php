<?php
/**
 * Created by PhpStorm.
 * User: zhulong-book6
 * Date: 2017/3/14
 * Time: 16:00
 */

use weblistenner\work;
use weblistenner\connection;

require_once __DIR__ . '/app.php';

$host = '0.0.0.0';
$port = '9998';
$protocol = 'websocket';

$worker = new work($host,$port,$protocol);
$arr = array(
    'username' => 'liming',
    'sex'   => 'boy',
);

$worker->onConnect = function ($connection)
{
    log_daily()->debug('连接成功了');
    $connection->send('33',true);
};

$worker->onMessage = function($connection,$data)
{
    log_daily()->debug("收到信息{$data}");
    if(intval($data) > 0){
        $connection->send($data + 1);
    }else{
        $connection->send("请发数字给我");
    }
};

$worker->onClose = function ($connection)
{

};

$worker->job();