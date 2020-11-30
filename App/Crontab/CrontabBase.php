<?php

namespace App\Crontab;

use EasySwoole\RedisPool\Redis;

class CrontabBase
{
    function withoutOverlapping($className, $ttl = 86400): bool
    {
        //返回true是可以执行，返回false是不能执行
        $name = explode("\\", $className);

        $name = end($name);

        $redis = Redis::defer('redis');

        $redis->select(14);

        $status = $redis->setNx($name, 'isRun') == 1 ? true : false;

        $status === false ?: $redis->expire($name, $ttl);

        return $status;
    }

    function removeOverlappingKey($className)
    {
        $name = explode("\\", $className);

        $name = end($name);

        $redis = Redis::defer('redis');

        $redis->select(14);

        return $redis->del($name);
    }


}
