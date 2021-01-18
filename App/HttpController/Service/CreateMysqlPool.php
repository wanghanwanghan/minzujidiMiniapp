<?php

namespace App\HttpController\Service;

use EasySwoole\Component\Singleton;
use EasySwoole\Mysqli\Client;
use EasySwoole\Mysqli\Config;
use EasySwoole\Pool\AbstractPool;
use EasySwoole\Pool\Manager;

class CreateMysqlPool extends AbstractPool
{
    use Singleton;

    protected $mysqlConf;

    function __construct()
    {
        parent::__construct(new \EasySwoole\Pool\Config());

        $mysqlConf = new Config([
            'host' => '127.0.0.1',
            'port' => '63306',
            'user' => 'chinaiiss',
            'password' => 'zbxlbj@2018*()',
            'database' => 'miniapp',
            'timeout' => 5,
            'charset' => 'utf8mb4',
        ]);

        $this->mysqlConf = $mysqlConf;
    }

    protected function createObject()
    {
        return new Client($this->mysqlConf);
    }

    //注册连接池，只能在mainServerCreate中用
    function createMysql()
    {
        Manager::getInstance()->register(CreateMysqlPool::getInstance(), 'miniapp');

        return true;
    }
}
