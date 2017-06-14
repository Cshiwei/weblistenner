<?php
/**
 * Created by PhpStorm.
 * User: csw
 * Date: 2017/3/14
 * Time: 18:01
 */
namespace weblistenner\protocol;

class unix extends socket implements protocol{

    public function send($msg)
    {
        // TODO: Implement send() method.
    }

    public function job()
    {
        // TODO: Implement job() method.
    }

    protected function dealBuf($buf)
    {
        // TODO: Implement dealBuf() method.
    }
}