<?php

namespace App\HttpController\Business\Api\Notify;

use App\HttpController\Business\BusinessBase;
use App\HttpController\Models\Api\Order;
use App\HttpController\Service\Pay\wx\wxPayService;
use EasySwoole\Pay\Pay;
use EasySwoole\Pay\WeChat\WeChat;
use EasySwoole\RedisPool\Redis;

class NotifyController extends BusinessBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    //微信通知 信动
    function wxNotify()
    {
        $pay = new Pay();

        $content = $this->request()->getBody()->__toString();

        try {
            $data = $pay->weChat((new wxPayService())->getConf())->verify($content);
            $data = json_decode(json_encode($data), true);
        } catch (\Throwable $e) {
            $data = [];
        }

        $redis = Redis::defer('redis');
        $redis->set(date('Y-m-d-H-i-s'),jsonEncode($data));

        //出错就不执行了
        if (empty($data)) return true;

        //拿订单信息
        $orderInfo = Order::create()->where('orderId', $data['out_trade_no'])->get();

        if (empty($orderInfo)) return true;

        //检查回调中的支付状态
        if (strtoupper($data['result_code']) === 'SUCCESS') {
            //支付成功
            $status = 3;
        } else {
            //支付失败
            $status = 4;
        }

        //更改订单状态
        $orderInfo->update(['status' => $status]);

        return $this->response()->write(WeChat::success());
    }

}