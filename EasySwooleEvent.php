<?php

namespace EasySwoole\EasySwoole;

use App\HttpController\Service\CreateMysqlOrm;
use App\HttpController\Service\CreateMysqlPool;
use App\HttpController\Service\CreateRedisPool;
use EasySwoole\Socket\Dispatcher;
use App\WebSocket\WebSocketParser;
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
        define('STATIC_PATH', __DIR__ . DIRECTORY_SEPARATOR . 'Static' . DIRECTORY_SEPARATOR);
        define('FILE_PATH', __DIR__ . DIRECTORY_SEPARATOR . 'Static' . DIRECTORY_SEPARATOR . 'File' . DIRECTORY_SEPARATOR);
        define('CERT_PATH', __DIR__ . DIRECTORY_SEPARATOR . 'Static' . DIRECTORY_SEPARATOR . 'Cert' . DIRECTORY_SEPARATOR);

        CreateMysqlPool::getInstance()->createMysql();
        CreateMysqlOrm::getInstance()->createMysqlOrm();
        CreateRedisPool::getInstance()->createRedis();

        /**
         * **************** websocket控制器 **********************
         */
        // 创建一个 Dispatcher 配置
        $conf = new \EasySwoole\Socket\Config();
        // 设置 Dispatcher 为 WebSocket 模式
        $conf->setType(\EasySwoole\Socket\Config::WEB_SOCKET);
        // 设置解析器对象
        $conf->setParser(new WebSocketParser());
        // 创建 Dispatcher 对象 并注入 config 对象
        $dispatch = new Dispatcher($conf);
        // 给server 注册相关事件 在 WebSocket 模式下  on message 事件必须注册 并且交给 Dispatcher 对象处理
        $register->set(EventRegister::onMessage, function (\swoole_websocket_server $server, \swoole_websocket_frame $frame) use ($dispatch) {
            $dispatch->dispatch($server, $frame->data, $frame);
        });
    }

    public static function onRequest(Request $request, Response $response): bool
    {
        $response->withHeader('Access-Control-Allow-Origin', '*');
        $response->withHeader('Access-Control-Allow-Methods', 'GET, POST');
        $response->withHeader('Access-Control-Allow-Credentials', 'true');
        $response->withHeader('Access-Control-Allow-Headers', '*');
        return true;
    }

    public static function afterRequest(Request $request, Response $response): void
    {
    }
}