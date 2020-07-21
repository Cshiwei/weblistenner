<?php
/**
 * Created by PhpStorm.
 * User: csw
 * Date: 2017/3/15
 * Time: 15:01
 */
namespace weblistenner\connection;

use weblistenner\work;
use weblistenner\event\EventInterface;
use weblistenner\protocol\webSocket;

class connect {

    //一次性获取缓冲区内容的最大字节数
    const MAX_BUFFER=65535;

    //发送缓冲区的大小 一次发送的数据超过当前数值，则分批次发送
    const MAX_SEND_BUFFER = 1048576;

    //有效连接的id，随着链接增加自增
    public static $idRecord=1;

    //发送缓冲区将被填满
    public $sendBufferWillFull = false;

    /**当前连接句柄
     * @var
     */
    public $socket;

    public $protocol;

    /**csw
     * 当前连接的一些系统信息
     */
    private $connectionMsg = array();

    private $customMsg='';

    public $onMessage;

    public $onClose;

    //接收缓存
    public $_recvBuffer;

    //发送缓存
    public $_sendBuffer;

    //本次连接的id
    public $id;

    //是否已经握手
    public $isHandShake=false;

    //握手动作前缓存的数据
    public $dataBeforeHand;

    //缓冲区内容
    public $frameData;

    /**csw 禁止通过new来实例化
     * connect constructor.
     * @param $socket
     */
    public function __construct($socket)
    {
        $this->socket = $socket;
        $this->id = self::$idRecord ++;
        work::$event->add($socket,EventInterface::EV_READ,array($this,'baseRead'));
    }

    //基础读取方法，分析缓冲区的内容
    public function baseRead($socket,$check_eof=true)
    {
        $buffer = fread($socket,self::MAX_BUFFER);
        if ($buffer === '' || $buffer === false)
        {
            if ($check_eof && (feof($socket) || !is_resource($socket) || $buffer === false))
            {
                $this->destroy();
                return;
            }
        }
        else
        {
            $this->_recvBuffer .= $buffer;
        }
        if($this->protocol)
        {
            $parser = $this->protocol;
            webSocket::input($this->_recvBuffer,$this);
            if(!empty($this->frameData))
            {
                foreach ($this->frameData as $key=>$val)
                {
                    if($this->onMessage)
                    {
                        call_user_func($this->onMessage,$this,$val);
                    }
                }
                $this->frameData = array();
            }
        }
    }

    /**向客户端发送数据
     * @param $sendBuffer
     * @param bool $isEncode
     * @return null
     */
    public function send($sendBuffer,$isEncode=false)
    {
        log_daily('system')->debug('sendBuffer'.$sendBuffer);
        if (true === $isEncode && $this->protocol)
        {
            $parser      = $this->protocol;
            $sendBuffer = webSocket::output($sendBuffer, $this);
            if ($sendBuffer === '')
            {
                return null;
            }
        }
        //如果有缓存数据还未发送
        if($this->_sendBuffer)
        {
            //缓冲区已经满载丢弃本次数据包
            if($this->checkSendBufferFull())
                return false;
        }
        else
        {
            $len = @fwrite($this->socket, $sendBuffer);
            log_daily('system')->debug('len'.$len);
            //表示发送成功
            if ($len === strlen($sendBuffer))
            {
                return true;
            }
            //发送了部分字节,将其他字节缓存起来
            if ($len > 0)
            {
                $this->_sendBuffer = substr($sendBuffer, $len);
            }
            else
            {
                if (!is_resource($this->socket) || feof($this->socket))
                {
                    $this->destroy();
                    return false;
                }
                $this->_sendBuffer = $sendBuffer;
            }
            work::$event->add($this->socket, EventInterface::EV_WRITE, array($this, 'baseWrite'));
            return null;
        }
    }

    //关闭当前连接
    public function close()
    {
        $this->destroy();
    }

    /**如果socket处于可写入的状态，则会调用本方法将_sendBuffer中的数据发送到客户端
     * @return bool
     * @internal param $socket
     */
    public function baseWrite()
    {
        $len = @fwrite($this->socket, $this->_sendBuffer);
        if ($len === strlen($this->_sendBuffer)) {
            work::$event->del($this->socket, EventInterface::EV_WRITE);
            $this->_sendBuffer = '';
            return true;
        }
        if ($len > 0)
        {
            $this->_sendBuffer = substr($this->_sendBuffer, $len);
        }
        else
        {
            $this->destroy();
        }
    }

    /**检测待发送数据是否会超出缓冲区限制的长度
     *
     */
    private function checkSendBufferFull()
    {
        $dataLength = strlen($this->_sendBuffer);
        //如果sengbuffer里的数据长度已经超出了规定的最大长度，则丢弃本次发送的数据
        if($dataLength >= self::MAX_SEND_BUFFER)
            return true;

        return false;
    }

    //释放连接
    public function destroy()
    {
        //去除该socket连接上绑定的读事件
        work::$event->del($this->socket, EventInterface::EV_READ);
        @fclose($this->socket);
        unset(work::$connections[$this->id]);
        if($this->onClose)
        {
            call_user_func($this->onClose,$this);
        }
    }


}