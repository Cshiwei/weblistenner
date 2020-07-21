<?php
/**
 * Created by PhpStorm.
 * User: csw
 * Date: 2020/7/10
 * Time: 15:53
 */

$sockTcp = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
socket_bind($sockTcp,'0.0.0.0',9877);
socket_listen($sockTcp,2);
while (true)
{
    $connection = socket_accept($sockTcp);
    echo "新连接来了\n";
    $buffer = "welcome new connection";
    $resLength = socket_send($connection,$buffer,1,0);
    sleep(1);
    $buffer = "oh i forget something";
    $resLength = socket_send($connection,$buffer,strlen($buffer),0);
    echo "服务器发送数据[{$buffer}],数据长度为[{$resLength}]\n";
    socket_recv($connection,$resRead,100,0);
    echo "服务器接收数据[{$resRead}]\n";
}



