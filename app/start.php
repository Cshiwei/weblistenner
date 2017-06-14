<?php
/**
 * Created by PhpStorm.
 * User: zhulong-book6
 * Date: 2017/3/14
 * Time: 16:00
 */
use weblistenner\work;
require_once __DIR__ . '/lib/Autoloader.php';

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

};

$worker->onMessage = function($connection,$data)
{

};

$worker->onClose = function ($connection)
{

};

$worker->job();