<?php

namespace App\HttpController\Service\Pay\wx;

use App\HttpController\Service\CommonService;
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

        $cert[]='-----BEGIN CERTIFICATE-----';
        $cert[]='MIID/DCCAuSgAwIBAgIUdgrfbfOttpcmqFaPstYEKWH6zK0wDQYJKoZIhvcNAQEL';
        $cert[]='BQAwXjELMAkGA1UEBhMCQ04xEzARBgNVBAoTClRlbnBheS5jb20xHTAbBgNVBAsT';
        $cert[]='FFRlbnBheS5jb20gQ0EgQ2VudGVyMRswGQYDVQQDExJUZW5wYXkuY29tIFJvb3Qg';
        $cert[]='Q0EwHhcNMjAxMTA2MDcxNDA3WhcNMjUxMTA1MDcxNDA3WjCBjTETMBEGA1UEAwwK';
        $cert[]='MTYwMzg0NzYwNzEbMBkGA1UECgwS5b6u5L+h5ZWG5oi357O757ufMTkwNwYDVQQL';
        $cert[]='DDDmsJHml4/mlofljJbliJvmhI/kuqfkuJrlrbXljJbln7rlnLDmnInpmZDlhazl';
        $cert[]='j7gxCzAJBgNVBAYMAkNOMREwDwYDVQQHDAhTaGVuWmhlbjCCASIwDQYJKoZIhvcN';
        $cert[]='AQEBBQADggEPADCCAQoCggEBAJo7LMka9pxJ0l/8IuqofTouiTyEV5/I4hrnZKNA';
        $cert[]='TL6IyjLtpCGPa//GWS8a0WrvQEYk5ZWbh4vruipbojU+D7fsoutVVrthr/Z/WlzB';
        $cert[]='qvNJDSV3w1TOHBb/qHjh1qTHTMBWdeQAEy7RVoXGy911R79Ta5Khk5KVqihbqb3A';
        $cert[]='Hn74pXUM2AEQiaTT8RUpDuUcUHmH38UeFKbjn2hpId75cTVZKmP5JKi8W2ReKw5/';
        $cert[]='NUm7pm0b7QFk9j/TiXm059Q9vWPByzqQGWrMGzUAPyyD0e+p8vdUAifXtWQ1XwWW';
        $cert[]='ZKRM0iNf+EbVYs157smD4Ty7uBbkfQuBGX4n5uz2ZUwrUvUCAwEAAaOBgTB/MAkG';
        $cert[]='A1UdEwQCMAAwCwYDVR0PBAQDAgTwMGUGA1UdHwReMFwwWqBYoFaGVGh0dHA6Ly9l';
        $cert[]='dmNhLml0cnVzLmNvbS5jbi9wdWJsaWMvaXRydXNjcmw/Q0E9MUJENDIyMEU1MERC';
        $cert[]='QzA0QjA2QUQzOTc1NDk4NDZDMDFDM0U4RUJEMjANBgkqhkiG9w0BAQsFAAOCAQEA';
        $cert[]='gCyU7c2JBPYiOEp2qKr3hNqya4VIcDEI/L5vQMIm4r9lTM47ZtssPqPtoMCbhekI';
        $cert[]='OzC9fpyg+U4Zga2XiYRqGq/1l9ICsTF+VohhpYftL6+a9cJ3FWWcyGlamVcpQVpW';
        $cert[]='iExNdzRg+B9yz9L03IUbe8/U8m3kf9Hc8sj4pXdkzvTAnLa/5PMR2Jwlrr6Z44wu';
        $cert[]='i89D68MmqrfnGZPJ+Ellb0dy+Pf6KjIV2xqdEhGnq0pg4Y0I25E9cjUsh+s1Wo8Q';
        $cert[]='DIMS3FPte1bEPLTcwHvr3Oyrx40jyLtt1apbWzELkOHMdBZvPqHyLuZC2uhxYKwr';
        $cert[]='oWYTls7dps5jsOsB8lOnag==';
        $cert[]='-----END CERTIFICATE-----';

        $key[]='-----BEGIN PRIVATE KEY-----';
        $key[]='MIIEvwIBADANBgkqhkiG9w0BAQEFAASCBKkwggSlAgEAAoIBAQCaOyzJGvacSdJf';
        $key[]='/CLqqH06Lok8hFefyOIa52SjQEy+iMoy7aQhj2v/xlkvGtFq70BGJOWVm4eL67oq';
        $key[]='W6I1Pg+37KLrVVa7Ya/2f1pcwarzSQ0ld8NUzhwW/6h44dakx0zAVnXkABMu0VaF';
        $key[]='xsvddUe/U2uSoZOSlaooW6m9wB5++KV1DNgBEImk0/EVKQ7lHFB5h9/FHhSm459o';
        $key[]='aSHe+XE1WSpj+SSovFtkXisOfzVJu6ZtG+0BZPY/04l5tOfUPb1jwcs6kBlqzBs1';
        $key[]='AD8sg9HvqfL3VAIn17VkNV8FlmSkTNIjX/hG1WLNee7Jg+E8u7gW5H0LgRl+J+bs';
        $key[]='9mVMK1L1AgMBAAECggEBAJFIouSCIMKSi7jdQ2r28pfnFGHMbNpYmh0r0dIylNh6';
        $key[]='9LXKw+zVNLAJASPtSE5KN6qbwSUQEDxO/tw922v6HUZtg/7ZMc1rtR0nVFDCIq3w';
        $key[]='J5Ee4wK08SL9C49rdg1crEEWcREovOlCSxXTcWEYxFBHXbMPv3q6v0IrLpdPLar+';
        $key[]='SkATj3z51p1GbQcdd2XwTZkM2Ub3AYOgglI3NLrvgvjHmdMlGdXRBW3PcfCvVdRo';
        $key[]='Yaqp6CCvDnSL7KYeR64xzfN8lWuwa93wtThjQ7BuDzOK5/sGdRc/qwY2Z0Nqgx1D';
        $key[]='dnTwq7D65J0SI0l7ucVrrD6qt+04eJF0qvECai6lj6ECgYEAyaL+vNl0sFwhNbsu';
        $key[]='vjYs0F2qra5fclhjmSnJTb99RUoiiiRqZ5ZINRLUrsB7QT7ZD1K/vTUOWe35rsjK';
        $key[]='SP6oMKO0JT92O8SF63nMEhOpJCmb296QDHefSSX78iMqSxjYrL+oKO9NM6D1f/5r';
        $key[]='HurbcE3PR2HFi2f1vN8tp+M5OM0CgYEAw9A9VuUSniJZSxi2ztjCzwDplKZUPisL';
        $key[]='lF0Hwky1ZJ2+1guR61Eqa1mqS7VX2jvy24/DzGXjRryJg6JHWu3gxXfktTPVXg1k';
        $key[]='sHeH6DqhbiKRmBk1FS8nUxoN9hF1uOKBEHnLeKJ7b0dcPJ6BsAHUVLJzM6Gg027n';
        $key[]='As5e1td8oskCgYEAw3lel1oAw9As6dgTEpeWjlGf20xGf6WsuJlH7DWNjhS2s1Fm';
        $key[]='ThHk4n7k7JzxYGk7KJ3B//1lck1AVu+VG3q1NqIdTFbfmkWExqmG6qdgvAwSau6y';
        $key[]='m0OEifdm7nCk2bS2qZILxdNn9ns+RN0yoAByx8bHAZ6JUgJwuq9ppW6k1KkCgYAe';
        $key[]='sOTsWbsxTfDw9E6y3Qarq1jxE6DgnZ6TAoHU0nEb9B2VLvsQBwi/Cq7GbwX2Dq9h';
        $key[]='+oaV4uJck/B50VWdyusQCFqwjA9FTpQZlKKYo5fpy0FGtay8RUfEnrDRgKsS722L';
        $key[]='R4u8vLrcFjM+zRlGnQLKw62KyiqK+Tb8GZyD0AKZAQKBgQCUymPe1HBuL85XZS26';
        $key[]='eH54i3CjTHY7mwGqZlRZ7eYyPDiNS1A8BX8RWZn08nFuhi7kU8hZ5C3ke2+tZzjh';
        $key[]='l7PfAjrN+cIEjEmduuKuBbGNmWuhLOTZR0X6dNlTAPK+LKhPraMAJE/+Vh5t+XIq';
        $key[]='9/Gt2Gpn1pWXs20K8W7pfKAdLg==';
        $key[]='-----END PRIVATE KEY-----';

        $conf->setApiClientCert(implode(PHP_EOL, $cert));
        $conf->setApiClientKey(implode(PHP_EOL, $key));

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

        CommonService::getInstance()->log4PHP($openId);

        $bean->setOpenid(end($openId));

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
