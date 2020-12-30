<?php

namespace App\HttpController\Service;

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

    private $ak = 'nIwv9FzOsr2xcAikWfWCG66dr7xfrvitYWAKBeVP';
    private $sk = '-H30p4scEq3kMdSLR2j0RdLoeR7AICvVwRtq9hWD';

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
        $config->setTimeout(60);//设置客户端连接超时时间
        $config->setMaxPackage(1024 * 1024 * 50);//设置包发送的大小：50M

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

        $res = $client->sendMessage('1328986196495314944', $tmp, ['code' => $code]);

        $redis = Redis::defer('redis');

        $redis->select(0);

        $redis->set(current($tmp) . $type, $code, 300);

        return empty(current($res)) ? '验证码发送失败' : '验证码发送成功';
    }

    //发送短信 审核失败01_miniapp
    function send_shenheshibai($phoneArr = [13511018713])
    {
        $tempId = '1336485113759805440';

        $ak = $this->ak;
        $sk = $this->sk;
        $auth = new Auth($ak, $sk);
        $client = new Sms($auth);

        $tmp = [];
        foreach ($phoneArr as $one) {
            $tmp[] = (string)$one;
        }

        $code = '';

        $res = $client->sendMessage($tempId, $tmp, ['code' => $code]);

        return empty(current($res)) ? '审核失败发送失败' : '审核失败发送成功';
    }

    //发送短信 办理成功01_miniapp
    function send_banlichenggong($phoneArr = [13511018713])
    {
        $tempId = '1336485535127973888';

        $ak = $this->ak;
        $sk = $this->sk;
        $auth = new Auth($ak, $sk);
        $client = new Sms($auth);

        $tmp = [];
        foreach ($phoneArr as $one) {
            $tmp[] = (string)$one;
        }

        $code = '';

        $res = $client->sendMessage($tempId, $tmp, ['code' => $code]);

        return empty(current($res)) ? '办理成功发送失败' : '办理成功发送成功';
    }

    //发送短信 出现风险01_admin
    function send_chuxianfengxian($phoneArr = [13511018713])
    {
        $tempId = '1336492830952009728';

        $ak = $this->ak;
        $sk = $this->sk;
        $auth = new Auth($ak, $sk);
        $client = new Sms($auth);

        $tmp = [];
        foreach ($phoneArr as $one) {
            $tmp[] = (string)$one;
        }

        $code = '';

        $res = $client->sendMessage($tempId, $tmp, ['code' => $code]);

        return empty(current($res)) ? '出现风险发送失败' : '出现风险发送成功';
    }

    //发送短信 新企业提交01_admin
    function send_xinqiyetijiao($phoneArr = [13511018713])
    {
        $tempId = '1336493162050367488';

        $ak = $this->ak;
        $sk = $this->sk;
        $auth = new Auth($ak, $sk);
        $client = new Sms($auth);

        $tmp = [];
        foreach ($phoneArr as $one) {
            $tmp[] = (string)$one;
        }

        $code = '';

        $res = $client->sendMessage($tempId, $tmp, ['code' => $code]);

        return empty(current($res)) ? '新企业提交发送失败' : '新企业提交发送成功';
    }












}
