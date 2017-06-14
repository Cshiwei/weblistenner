<?php
/**
 * Created by PhpStorm.
 * User: csw
 * Date: 2017/3/14
 * Time: 14:37
 */
namespace weblistenner\protocol;

use weblistenner\event\Select;
use weblistenner\event\EventInterface;
use weblistenner\connection\connect;

class socket{

    protected $connections;

    public $onConnect;

    public $onMessage;

    //unixsocket描述符，用于主进程子进程通信
    protected $socketPair;

    public static $event;

    /**csw
     * 读取的各个连接里的缓冲区里的内容
     * @var
     */
    protected $connectionBuf=array();

    public function __construct()
    {
        self::$event = new Select();
    }

    public function create($domain,$type,$protocol,$host,$port)
    {
        $context_option['socket']['backlog'] = 102400;
        $flags = STREAM_SERVER_BIND | STREAM_SERVER_LISTEN;
        $context = stream_context_create($context_option);;
        $local_socket = 'tcp' . '://' . $host . ':' . $port;
        $sock = stream_socket_server($local_socket, $errno, $errmsg, $flags, $context);
        return $sock;
    }

    /**接收连接
     * @param $socket
     */
    public function acceptConnection($socket)
    {
        $new_socket =  @stream_socket_accept($socket, 0, $remote_address);

        if (!$new_socket) {
            return;
        }

        $connection = new connect($new_socket);
        $connection->onMessage = $this->onMessage;

        if($this->onConnect)
        {
            call_user_func($this->onConnect,$connection);
        }
    }

    protected function strToBit($str,$isArr=true)
    {
        $byteLength = strlen($str);       //字符串占用的字节数，不是占位符个个数
        $byteArr = array();
        for($i=0;$i<$byteLength;$i++)
        {
            $byteArr[] = str_pad(decbin(ord($str[$i])),8,'0',STR_PAD_LEFT);
        }

        if($isArr)
            return $byteArr;

        $byteStr = implode('',$byteArr);
        return $byteStr;
    }

    /**连接建立时触发
     * @param $callback
     */
    public function onConnect($callback)
    {
        $this->onConnect = $callback;
    }

    /**收到消息时触发
     * @param $callback
     */
    public function onReceiveMsg($callback)
    {
        $this->onMessage = $callback;
    }

    /**关闭连接时触发
     *
     */
    public function onClose()
    {

    }

    /**初始化连接
     * @param $connection
     */
    protected function initConnect($connection)
    {
        $this->connect->init($connection);
        empty($this->onConnect) OR call_user_func($this->onConnect,$this);
        $connectionMsg = $this->connect->getMsg();
        $this->addConnection($connectionMsg);
        $this->getALLConnections();
    }

    /**csw
     * 读取各个连接缓冲区的数据
     */
    protected function receiveBuf()
    {
        if(!empty($this->connections))
        {
            echo "遍历所有连接，读取缓冲区内容！\n";
            foreach ($this->connections as $key=>$connection)
            {
                //连接断开之后将返回内容，否则将会阻塞
                $buf = socket_read($connection['connection'],2048);
               /* do
                {
                    $buf = '';
                    $res = socket_recv($connection['connection'],$buf,2048,0);
                    $temp.=$buf;
                }while($res!=false);*/
                $this->connections[$key]['_buf'] = $buf;
                sleep(1);
            }
            echo "遍历完全，打印所有连接！\n";
            var_dump($this->connections);
        }
    }

    /**csw
     * 读取缓冲区内容并解析报信息
     */
    protected function listenMsg()
    {
        if(!empty($this->connections))
        {
            echo "遍历解析缓冲区内容！\n";
            foreach ($this->connections as $key=>$connection)
            {
                if(isset($connection['_buf']))
                {
                    $buf = $connection['_buf'];
                    $buf = $this->dealBuf($buf);
                    echo "缓冲区内容打印：\n";
                    var_dump($buf);
                    /*foreach ($buf as $ke=>$va)
                    {
                        $this->connect->init($connection['connection']);
                        call_user_func_array($this->onMessage,array($this,$va['data']));
                    }*/
                }

            }
        }
    }

    /**对缓冲区的内容进行处理
     * @param $buf
     * @return mixed
     */
    protected function dealBuf($buf)
    {

    }

    /**csw
     * 注册信息连接到connects数组
     * @param $connectionMsg
     */
    protected function addConnection($connectionMsg)
    {
        $this->connections[] = $connectionMsg;
    }

    /**csw
     * 添加自定义信息到connection数组
     * @param $arr
     */
    public function addMsg($arr)
    {
        $this->connect->addMsg($arr);
    }

    /**获取所有的连接信息，数组形式
     *
     */
    public function getALLConnections()
    {
        echo "当前连接的客户端信息:";
        print_r($this->connections);
    }

}