<?php

namespace App\HttpController\Service;

use EasySwoole\Component\Singleton;
use EasySwoole\Redis\Config\RedisConfig;
use EasySwoole\RedisPool\Redis;

class CreateRedisPool extends ServiceBase
{
    use Singleton;

    //注册redis连接池，只能在mainServerCreate中用
    public function createRedis()
    {
        $conf=new RedisConfig();
        $conf->setHost('39.105.42.192');
        $conf->setPort('56379');
        $conf->setTimeout(5);
        $conf->setAuth('wanghan123');
        $conf->setSerialize(RedisConfig::SERIALIZE_NONE);

        $redisPoolConfig=Redis::getInstance()->register('redis',$conf);
        $redisPoolConfig->setMinObjectNum(10);
        $redisPoolConfig->setMaxObjectNum(20);
        $redisPoolConfig->setAutoPing(10);

        return true;
    }
}
