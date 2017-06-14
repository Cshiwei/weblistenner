<?php
/**
 * Created by PhpStorm.
 * User: zhulong-book6
 * Date: 2017/6/8
 * Time: 19:23
 */
include 'lib/event/EventInterface.php';
include 'lib/event/Select.php';

function test()
{
    echo "66666";
}

$select = new Select();

$sock = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
if ($sock)
    socket_bind($sock, '0.0.0.0', '8283');
socket_listen($sock);

$select::add($sock, $select::EV_READ, 'test');
$select::loop();