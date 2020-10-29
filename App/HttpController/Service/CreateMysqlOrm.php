<?php

namespace App\HttpController\Service;

use EasySwoole\Component\Singleton;
use EasySwoole\ORM\Db\Config;
use EasySwoole\ORM\Db\Connection;
use EasySwoole\ORM\DbManager;

class CreateMysqlOrm extends ServiceBase
{
    use Singleton;

    //注册
    public function createMysqlOrm()
    {
        $config = new Config();

        //数据库配置
        $config->setHost('39.105.42.192');
        $config->setPort('63306');
        $config->setUser('chinaiiss');
        $config->setPassword('zbxlbj@2018*()');
        $config->setDatabase('miniapp');
        $config->setCharset('utf8mb4');

        //链接池配置
        $config->setGetObjectTimeout(3.0); //设置获取连接池对象超时时间
        $config->setIntervalCheckTime(30 * 1000); //设置检测连接存活执行回收和创建的周期
        $config->setMaxIdleTime(15); //连接池对象最大闲置时间(秒)
        $config->setMaxObjectNum(50); //设置最大连接池存在连接对象数量
        $config->setMinObjectNum(5); //设置最小连接池存在连接对象数量
        $config->setAutoPing(5); //设置自动ping客户端链接的间隔

        DbManager::getInstance()->addConnection(new Connection($config), 'miniapp');

        return true;
    }
}
