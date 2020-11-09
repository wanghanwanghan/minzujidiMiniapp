<?php

namespace App\HttpController\Service\Pay\wx;

use App\HttpController\Service\HttpClient\CoHttpClient;
use EasySwoole\Pay\Pay;
use EasySwoole\Pay\WeChat\Config as wxConf;
use EasySwoole\Pay\WeChat\RequestBean\MiniProgram;

class wxPayService
{
    function getConf(): wxConf
    {
        $conf = new wxConf();

        $conf->setMiniAppId('wx864577e52e8277a2');
        $conf->setMchId('1603847607');
        $conf->setKey('qqaazzwwssxxeeddccrrffvvttggbbyy');
        $conf->setNotifyUrl('https://mzjd.meirixindong.com/api/v1/notify/wx');
        $conf->setApiClientCert(CERT_PATH.'apiclient_cert.pem');
        $conf->setApiClientKey(CERT_PATH.'apiclient_key.pem');

        return $conf;
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

        $bean->setOpenid($openId);

        //订单号
        $bean->setOutTradeNo($orderId);

        //订单body
        $bean->setBody($body);

        //金额
        //$bean->setTotalFee($money * 100);
        $bean->setTotalFee(1);

        //终端ip，据说高版本不用传了
        if (!empty($ipForCli)) $bean->setSpbillCreateIp($ipForCli);

        $pay = new Pay();

        $params = $pay->weChat($this->getConf())->miniProgram($bean);

        return $params;
    }


}
