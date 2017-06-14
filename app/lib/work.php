<?php
/**
 * Created by PhpStorm.
 * User: csw
 * Date: 2017/3/14
 * Time: 15:56
 * 工作类
 * 支持协议：websocket
 */
namespace weblistenner;

use weblistenner\connection\connect;
use weblistenner\protocol\webSocket;
use weblistenner\protocol\unix;
use weblistenner\event\Select;
use weblistenner\event\EventInterface;

class work{

    //主机地址
    private $host;

    //服务端口
    private $port;

    //服务协议
    private $protocol;

    //事件处理类
    public static $event;

    //连接建立时的回调方法
    public $onConnect;

    //接收到新消息时触发
    public $onMessage;

    //连接关闭时触发(当前连接已经断开)
    public $onClose;

    //当前在线的连接
    public static $connections;

    //支持的协议
    private $protocolArr = array(
        'websocket' => 'webSocket',
        'unix'  => 'unix',
    );

    /**
     * work constructor.
     * @param $host | 主机地址
     * @param $port | 端口号
     * @param $protocol | 协议: websocket
     */
    public function __construct($host,$port,$protocol)
    {
        $this->host = $host;
        $this->port = $port;
        if(isset($protocol,$this->protocolArr))
        {
            $this->protocol = $this->protocolArr[$protocol];
        }

        self::$event = new Select();
    }

    public function job()
    {
        $this->listen();
    }

    private function listen()
    {
        $flags = STREAM_SERVER_BIND | STREAM_SERVER_LISTEN;
        $context = stream_context_create();;
        $local_socket = 'tcp' . '://' . $this->host . ':' . $this->port;
        $sock = stream_socket_server($local_socket, $errno, $errmsg, $flags, $context);
        self::$event->add($sock,EventInterface::EV_READ,array($this, 'acceptConnection'));
        self::$event->loop();
    }

    //有新连接时触发
    public function acceptConnection($socket)
    {
        $new_socket =  @stream_socket_accept($socket, 0, $remote_address);

        if(!$new_socket)
            return;
        $connection = new connect($new_socket);
        self::$connections[$connection->id] = $connection;
        $connection->onMessage = $this->onMessage;
        $connection->protocol = $this->protocol;
        $connection->onClose = $this->onClose;

        if($this->onConnect)
        {
            call_user_func($this->onConnect,$connection);
        }
    }
}