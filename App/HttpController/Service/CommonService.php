<?php

namespace App\HttpController\Service;

use EasySwoole\Component\Singleton;
use EasySwoole\RedisPool\Redis;
use Qiniu\Auth;
use Qiniu\Sms\Sms;
use wanghanwanghan\someUtils\control;

class CommonService extends ServiceBase
{
    use Singleton;

    private $ak = 'qkAGCPMeecr8hCJER5JcamUPwGFcbq7sKAS2QemL';
    private $sk = 'dYNKNeodv0YDHIDaV_Iw-t8fuUqZSSSdlidZCYot';

    function __construct()
    {
        return parent::__construct();
    }

    //写log
    function log4PHP($content, $type = 'info', $filename = '')
    {
        (!is_array($content) && !is_object($content)) ?: $content = json_encode($content);


        var_dump(LOG_PATH);


        return control::writeLog($content, LOG_PATH, $type, $filename);
    }

    //发送短信
    function vCodeSend($phoneArr, $type)
    {
        $ak = $this->ak;
        $sk = $this->sk;
        $auth = new Auth($ak, $sk);
        $client = new Sms($auth);

        $tmp = [];
        foreach ($phoneArr as $one) {
            $tmp[] = (string)$one;
        }

        mt_srand();
        $code = mt_rand(100000, 999999);

        $res = $client->sendMessage('1314418176716455936', $tmp, ['code' => $code]);

        $redis = Redis::defer('redis');

        $redis->select(0);

        $redis->set(current($tmp) . $type, $code, 300);

        return empty(current($res)) ? '验证码发送失败' : '验证码发送成功';
    }
}
