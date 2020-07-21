<?php
/**
 * Created by PhpStorm.
 * User: csw
 * Date: 2020/7/10
 * Time: 16:31
 */

$max = 1;
for($i=1;$i<=$max;$i++) {
    $sockTcp = socket_create(AF_INET, SOCK_STREAM, 0);
    socket_connect($sockTcp, '127.0.0.1', 9877);
    sleep(2);
    socket_recv($sockTcp,$resRead,1000,0);
    echo "客户端读取数据[{$resRead}]\n";
    $buffer = "yes i am conection1";
    $resSend = socket_send($sockTcp,$buffer,strlen($buffer),0);
    echo "客户端发送数据[{$buffer}],长度为[{$resSend}]\n";
}





