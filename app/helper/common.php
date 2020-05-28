<?php
/**
 * Created by PhpStorm.
 * User: csw
 * Date: 2020/5/28
 * Time: 17:32
 */
//打印日志
if (!function_exists('log_daily')) {
    function log_daily($name = 'listener')
    {
        return new Monolog\Logger('lumen', [
            (new Monolog\Handler\StreamHandler(dirname(__DIR__) . '/storage/logs/' . $name . '.log', Monolog\Logger::DEBUG))
                ->setFormatter(new Monolog\Formatter\LineFormatter(null, null, true, true))
        ]);
    }
}
