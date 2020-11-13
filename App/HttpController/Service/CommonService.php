<?php

namespace App\HttpController\Service;

use App\HttpController\Service\Common\EmailTemplate\Template01;
use App\HttpController\Service\Common\EmailTemplate\Template02;
use EasySwoole\Component\Singleton;
use EasySwoole\RedisPool\Redis;
use EasySwoole\Smtp\Mailer;
use EasySwoole\Smtp\MailerConfig;
use EasySwoole\Smtp\Message\Attach;
use EasySwoole\Smtp\Message\Html;
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

    //发送邮件
    function sendEmail($sendTo, array $addAttachment)
    {
        $config = new MailerConfig();
        $config->setServer('smtp.exmail.qq.com');
        $config->setSsl(true);
        $config->setPort(465);
        $config->setUsername('mail@meirixindong.com');
        $config->setPassword('1q2w3e4r%T');
        $config->setMailFrom('mail@meirixindong.com');
        $config->setTimeout(10);//设置客户端连接超时时间
        $config->setMaxPackage(1024 * 1024 * 5);//设置包发送的大小：5M

        //设置文本或者html格式
        $mimeBean = new Html();
        $mimeBean->setSubject('民族基地');
        $mimeBean->setBody('');

        //添加附件
        if (!empty($addAttachment)) {
            foreach ($addAttachment as $onePathAndFilename) {
                $mimeBean->addAttachment(Attach::create($onePathAndFilename));
            }
        }
        $mailer = new Mailer($config);
        //发送邮件
        $mailer->sendTo($sendTo, $mimeBean);

        return true;
    }
}
