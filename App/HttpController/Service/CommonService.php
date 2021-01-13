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
    function sendEmail($sendTo, array $addAttachment, $setSubject = '民族基地')
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
        $mimeBean->setSubject($setSubject);
        $mimeBean->setBody('');

        //添加附件
        if (!empty($addAttachment)) {
            foreach ($addAttachment as $onePathAndFilename) {
                $mimeBean->addAttachment(Attach::create($onePathAndFilename));
            }
        }
        $mailer = new Mailer($config);
        //发送邮件
        CommonService::getInstance()->log4PHP($mailer->sendTo($sendTo, $mimeBean));

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

        CommonService::getInstance()->log4PHP($res);

        $redis = Redis::defer('redis');

        $redis->select(0);

        $redis->set(current($tmp) . $type, $code, 300);

        return empty(current($res)) ? '验证码发送失败' : '验证码发送成功';
    }

    //发送短信 审核失败02_miniapp
    function send_shenheshibai($phoneArr = [13511018713])
    {
        $tempId = '1349188609541943296';

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

    //发送短信 办理成功02_miniapp
    function send_banlichenggong($phoneArr = [13511018713])
    {
        $tempId = '1349189035637096448';

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
    function send_chuxianfengxian($phoneArr = [13511018713,13611180976,18618457910])
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
    function send_xinqiyetijiao($phoneArr = [13511018713,13611180976,18618457910])
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

    //发送短信 信息表通知01_miniapp
    function send_xxbtz($phoneArr = [13511018713,13611180976,18618457910])
    {
        $tempId = '1344261606464307200';

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

        return empty(current($res)) ? '信息表通知发送失败' : '信息表通知发送成功';
    }

    //发送短信 协议通知01_miniapp
    function send_xytz($phoneArr = [13511018713,13611180976,18618457910])
    {
        $tempId = '1344261807816065024';

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

        return empty(current($res)) ? '协议通知发送失败' : '协议通知发送成功';
    }

    /**
     *数字金额转换成中文大写金额的函数
     *String Int  $num  要转换的小写数字或小写字符串
     *return 大写字母
     *小数位为两位
     **/
    function toChineseNumber($num)
    {
        $c1 = '零壹贰叁肆伍陆柒捌玖';
        $c2 = '分角元拾佰仟万拾佰仟亿';
        $num = round($num, 2);
        $num = $num * 100;
        if (strlen($num) > 10) {
            return '数据太长，没有这么大的钱吧，检查下';
        }
        $i = 0;
        $c = '';
        while (1) {
            if ($i == 0) {
                $n = substr($num, strlen($num) - 1, 1);
            } else {
                $n = $num % 10;
            }
            $p1 = substr($c1, 3 * $n, 3);
            $p2 = substr($c2, 3 * $i, 3);
            if ($n != '0' || ($n == '0' && ($p2 == '亿' || $p2 == '万' || $p2 == '元'))) {
                $c = $p1 . $p2 . $c;
            } else {
                $c = $p1 . $c;
            }
            $i = $i + 1;
            $num = $num / 10;
            $num = (int)$num;
            if ($num == 0) {
                break;
            }
        }
        $j = 0;
        $slen = strlen($c);
        while ($j < $slen) {
            $m = substr($c, $j, 6);
            if ($m == '零元' || $m == '零万' || $m == '零亿' || $m == '零零') {
                $left = substr($c, 0, $j);
                $right = substr($c, $j + 3);
                $c = $left . $right;
                $j = $j - 3;
                $slen = $slen - 3;
            }
            $j = $j + 3;
        }

        if (substr($c, strlen($c) - 3, 3) == '零') {
            $c = substr($c, 0, strlen($c) - 3);
        }
        if (empty($c)) {
            return '零元整';
        } else {
            return $c . "整";
        }
    }








}
