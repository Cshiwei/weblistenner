<?php
/**
 * Created by PhpStorm.
 * User: csw
 * Date: 2017/3/14
 * Time: 15:50
 */
namespace weblistenner\protocol;
use weblistenner\event\EventInterface;
use weblistenner\connection\connect;

class webSocket implements protocol {

    const _domain = AF_INET;

    const _type = SOCK_STREAM;

    const _protocol = SOL_TCP;

    const BINARY_TYPE_BLOB = "\x81";

    const BINARY_TYPE_ARRAYBUFFER = "\x82";

    private static $customStr = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

    private $sock;

    private $host;

    private $port;

    public static $webSocketFrame;

    public static $frameLength;


    public static function input($buffer, connect $connection)
    {
        //获取缓冲区内容的字节数
        $recvLen = strlen($buffer);

        //websocket数据包至少需要两个字节
        if ($recvLen < 2)
            return 0;

        if(!$connection->isHandShake)
        {
            return self::_handShake($buffer,$connection);
        }

        $firstByte    = ord($buffer[0]);
        $secondByte   = ord($buffer[1]);
        $dataLen     = $secondByte & 127;
        $isFinFrame = $firstByte >> 7;
        $masked       = $secondByte >> 7;
        $opCode       = $firstByte & 0xf;

        switch ($opCode)
        {
            //是中间数据包
            case 0x0;
                break;
            //标志一个text类型的数据包
            case 0x1;
                break;
            //标志一个二进制类型的数据包
            case 0x2;
                break;
            //标志一个关闭连接的数据包
            case 0x8:
                $connection->destroy();
                break;
            //标志一个ping类型的数据包
            case 0x9;
                break;
            //标志一个pong类型的数据包
            case 0xa;
                break;
            default :
                echo "无效的opCode[{$opCode}]\n";
                $connection->close();
                return 0;
        }

        // Calculate packet length.
        //如果数据长度为0-125，包头的长度是6byte
        // 4byte掩码 + (1bit Mask + 7bit dataLength) + (1bit fin + 3bit 扩展协议 + 4bit opCode)
        $headLen = 6;
        if ($dataLen === 126)
        {
            $head_len = 8; //多加2byte来表示dataLength
            if ($head_len > $recvLen)
            {
                return 0;
            }
            $pack     = unpack('nn/ntotal_len', $buffer);   //array('n'=>'','total_len'=>'')
            $dataLen = $pack['total_len'];
        }
        else
            {
            if ($dataLen === 127)
            {
                $head_len = 14;  //多加8byte表示dataLength
                if ($head_len > $recvLen) {
                    return 0;
                }
                $arr      = unpack('n/N2c', $buffer);   //array(0=>'','c1'=>'','c2');
                $dataLen = $arr['c1']*4294967296 + $arr['c2'];  //前四位 * 2^32 + 后四位
            }
        }
        $current_frame_length = $headLen + $dataLen;   //本次从客户端发送的frame的总长度（byte）
        //本次frame的长度小于本次读取的总长度，可以直接获取本次frame的数据
        if($isFinFrame)
        {
            if($current_frame_length > $recvLen)
            {
                return 0;
            }
            elseif($current_frame_length == $recvLen)
            {
                $current_frame_data = self::decode(substr($buffer,0,$current_frame_length));
                $connection->frameData[] = $current_frame_data;
                $connection->_recvBuffer = '';
            }
            else
            {
                $current_frame_data = self::decode(substr($buffer,0,$current_frame_length));
                $connection->frameData[] = $current_frame_data;
                $otherBuffer = substr($buffer,$current_frame_length);
                $connection->_recvBuffer = $otherBuffer;
                return self::input($otherBuffer,$connection);
            }
        }
        return 0;
    }

    //输出控制函数
    public static function output($sendBuffer,connect $connection)
    {
        $encode_buffer = self::encode($sendBuffer);

        if (!$connection->isHandShake)
        {
            if (empty($connection->dataBeforeHand))
            {
                $connection->dataBeforeHand = '';
            }
            if (strlen($connection->dataBeforeHand) > $connection::MAX_SEND_BUFFER)
            {
                return '';
            }
            $connection->dataBeforeHand .= $encode_buffer;
            return '';
        }
        return $encode_buffer;
    }

    //解码
    public static function decode($buffer)
    {
        $masks = $data = $decoded = null;
        $len = ord($buffer[1]) & 127;
        if ($len === 126) {
            $masks = substr($buffer, 4, 4);
            $data  = substr($buffer, 8);
        } else {
            if ($len === 127) {
                $masks = substr($buffer, 10, 4);
                $data  = substr($buffer, 14);
            } else {
                $masks = substr($buffer, 2, 4);
                $data  = substr($buffer, 6);
            }
        }
        for ($index = 0; $index < strlen($data); $index++)
        {
            $decoded .= $data[$index] ^ $masks[$index % 4];
        }
        return $decoded;
    }

    //编码
    public static function encode($sendBuffer)
    {
        $len = strlen($sendBuffer);
        $first_byte = self::BINARY_TYPE_BLOB;
        if ($len <= 125)
        {
            $encode_buffer = $first_byte . chr($len) . $sendBuffer;
        }
        else
        {
            if ($len <= 65535)
            {
                $encode_buffer = $first_byte . chr(126) . pack("n", $len) . $sendBuffer;
            }
            else
            {
                $encode_buffer = $first_byte . chr(127) . pack("xxxxN", $len) . $sendBuffer;
            }
        }
        return $encode_buffer;
    }

    /**对要发送的数据进行协议处理
     * @param $msg          //需要传送的 信息
     * @param $encode       //信息的编码方式 如果未指定则根据握手是的头部信息，否则默认utf-8
     */
    public function send($msg,$encode)
    {
        $this->strToBit($msg);
    }

    /**根据包头信息分解缓冲区内容
     * @param $buf
     * @return mixed|void
     */
    protected function dealBuf($buf)
    {
        $buf = $this->strToBit($buf);
        return $buf;
    }

    protected function frame()
    {

    }

    public function job()
    {
        $sock = $this->create(self::_domain,self::_type,self::_protocol,$this->host,$this->port);
        self::$event->add($sock,EventInterface::EV_READ,array($this, 'acceptConnection'));
        self::$event->loop();
    }

    /**实现websocket协议握手
     * @param $buffer  缓冲区的内容
     * @param $connection
     * @return int
     */
    private static function _handShake($buffer,connect $connection)
    {
        $offset = strpos($buffer,'Sec-WebSocket-Key');
        if($offset)
        {
            $clientKey = substr($buffer,$offset+19,24);
            $responseKey = base64_encode(sha1($clientKey.self::$customStr,true));
            $responseStr = "HTTP/1.1 101 Switching Protocols\r\nUpgrade:websocket\r\nConnection:Upgrade\r\nSec-WebSocket-Accept:{$responseKey}\r\n\r\n";
            $connection->isHandShake=true;
            $connection->send($responseStr);
            $connection->_recvBuffer = '';
        }
        else
        {
           $connection->destroy();
        }
        return 0;
    }

}