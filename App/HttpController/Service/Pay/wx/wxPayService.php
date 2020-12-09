<?php

namespace App\HttpController\Service\Pay\wx;

use App\HttpController\Models\Api\Order;
use App\HttpController\Models\Api\User;
use App\HttpController\Service\CommonService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use EasySwoole\Pay\Pay;
use EasySwoole\Pay\WeChat\Config as wxConf;
use EasySwoole\Pay\WeChat\RequestBean\MiniProgram;
use EasySwoole\Pay\WeChat\RequestBean\Refund;

class wxPayService
{
    function getConf(): wxConf
    {
        $conf = new wxConf();

        $conf->setAppId('wx864577e52e8277a2');
        $conf->setMiniAppId('wx864577e52e8277a2');
        $conf->setMchId('1603847607');
        $conf->setKey('qqaazzwwssxxeeddccrrffvvttggbbyy');
        $conf->setNotifyUrl('https://miniapp.minfuqifu.com/api/v1/notify/wx');
        $conf->setApiClientCert(CERT_PATH.'apiclient_cert.pem');
        $conf->setApiClientKey(CERT_PATH.'apiclient_key.pem');

        return $conf;
    }

    private function getAccessToken()
    {
        $data = [
            'appid' => 'wx864577e52e8277a2',
            'secret' => '2f88169a1bab8f461150483be40eb487',
            'grant_type' => 'client_credential',
        ];

        $url = 'https://api.weixin.qq.com/cgi-bin/token?' . http_build_query($data);

        return (new CoHttpClient())->setDecode(true)->send($url, $data, [], [], 'get');
    }

    private function getOpenId($code): array
    {
        $url = 'https://api.weixin.qq.com/sns/jscode2session';

        $data = [
            'appid' => 'wx864577e52e8277a2',
            'secret' => '2f88169a1bab8f461150483be40eb487',
            'js_code' => $code,//这是从wx.login中拿的
            'grant_type' => 'authorization_code',
        ];

        $url .= '?' . http_build_query($data);

        $res = (new CoHttpClient())->send($url, $data, [], [], 'get');

        return json_decode($res,true);
    }

    //返回一个小程序支付resp对象
    function miniAppPay(string $jsCode, string $orderId, string $money, string $body, string $ipForCli = '')
    {
        $bean = new MiniProgram();

        //用户的openid
        $openId = $this->getOpenId($jsCode);

        $openId = end($openId);

        //更新用户openid
        $phone = Order::create()->where('orderId',$orderId)->get()->phone;
        User::create()->where('phone',$phone)->update(['openid'=>$openId]);

        $bean->setOpenid($openId);

        //订单号
        $bean->setOutTradeNo($orderId);

        //订单body
        $bean->setBody($body);

        //金额
        //$bean->setTotalFee($money * 100);
        $bean->setTotalFee(100);

        //终端ip，据说高版本不用传了
        if (!empty($ipForCli)) $bean->setSpbillCreateIp($ipForCli);

        $pay = new Pay();

        $params = $pay->weChat($this->getConf())->miniProgram($bean);

        return $params;
    }

    //退款
    function refund($orderId,$money)
    {
        $refund = new Refund();
        $refund->setOutTradeNo($orderId);
        $refund->setOutRefundNo('TK' . date('YmdHis') . rand(1000, 9999));
        $money = $money * 100;
        $refund->setTotalFee($money);
        $refund->setRefundFee($money - $money * 0.006);

        $pay = new Pay();

        return $pay->weChat($this->getConf())->refund($refund);
    }

    //推送
    function push()
    {
        $access_token = $this->getAccessToken();
        $access_token = $access_token['access_token'];

        $url = "https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token={$access_token}";

        $data = [
            'access_token' => $access_token,
            'touser' => 'oJwPW5Bdz5CbBVOmi0IM10diL2-c',
            'template_id' => 'UbsCvA4YXkBG6l-i59dcKWhee-knfG6hw_4x3Pb-fHM',
            'page' => '/pages/detail/detail',
            'data' => [
                'time2' => ['value' => '2019年10月1日 15:01'],
                'amount3' => ['value' => '123123.00'],
                'character_string5' => ['value' => '1111111111111111111'],
                'time8' => ['value' => '2019年10月1日 15:01'],
                'thing4' => ['value' => '字符串2'],
            ],
            'miniprogram_state' => 'developer',
            'lang' => 'zh_CN',
        ];

        $res = (new CoHttpClient())->setDecode(true)->send($url,$data,[],[],'postJson');

        CommonService::getInstance()->log4PHP($res);
    }


}
