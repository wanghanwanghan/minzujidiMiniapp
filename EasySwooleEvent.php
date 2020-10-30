<?php

namespace EasySwoole\EasySwoole;

use App\HttpController\Service\CreateMysqlOrm;
use App\HttpController\Service\CreateMysqlPool;
use App\HttpController\Service\CreateRedisPool;
use EasySwoole\EasySwoole\Swoole\EventRegister;
use EasySwoole\EasySwoole\AbstractInterface\Event;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;

class EasySwooleEvent implements Event
{
    public static function initialize()
    {
        date_default_timezone_set('Asia/Shanghai');
    }

    public static function mainServerCreate(EventRegister $register)
    {
        define('LOG_PATH', __DIR__ . DIRECTORY_SEPARATOR . 'Log' . DIRECTORY_SEPARATOR);

        CreateMysqlPool::getInstance()->createMysql();
        CreateMysqlOrm::getInstance()->createMysqlOrm();
        CreateRedisPool::getInstance()->createRedis();
    }

    public static function onRequest(Request $request, Response $response): bool
    {
        return true;
    }

    public static function afterRequest(Request $request, Response $response): void
    {
    }
}