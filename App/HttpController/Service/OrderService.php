<?php

namespace App\HttpController\Service;

use App\HttpController\Models\Api\Order;
use Carbon\Carbon;
use EasySwoole\Component\Singleton;

class OrderService extends ServiceBase
{
    use Singleton;

    const ORDER_STATUS_1 = 1;//待确认
    const ORDER_STATUS_2 = 2;//待支付
    const ORDER_STATUS_3 = 3;//支付完成
    const ORDER_STATUS_4 = 4;//支付异常
    const ORDER_STATUS_5 = 5;//已退款

    function __construct()
    {
        return parent::__construct();
    }

    private function createOrderId($userType)
    {
        mt_srand();
        $ymd = Carbon::now()->format('YmdHis');
        return $ymd.$userType.mt_rand(0,9).'000000';
    }

    //创建订单
    function createOrder($phone,$userType,$taxType,$modifyAddr,$modifyArea,$areaFeeItems,$proxy,$tradeType)
    {
        $userType = (int)$userType;
        $taxType = (int)$taxType;
        $modifyAddr = (int)$modifyAddr;
        $modifyArea = (int)$modifyArea;
        $areaFeeItems = (string)$areaFeeItems;
        $proxy = (int)$proxy;
        $tradeType = (int)$tradeType;

        $insert=[];

        switch ($userType)
        {
            case 1://会员企业
                $insert=[
                    'orderId'=>$this->createOrderId($userType),
                    'phone'=>$phone,
                    'userType'=>$userType,
                    'modifyAddr'=>$modifyAddr,
                    'modifyArea'=>$modifyArea,
                    'areaFeeItems'=>$areaFeeItems,
                    'proxy'=>$proxy,
                    'status'=>self::ORDER_STATUS_1,
                    'price'=>(new ExprFee($userType,$taxType,$modifyAddr,$modifyArea,$areaFeeItems,$proxy))->expr(),
                ];
                break;
            case 2://新企业
                $insert=[
                    'orderId'=>$this->createOrderId($userType),
                    'phone'=>$phone,
                    'userType'=>$userType,
                    'taxType'=>$taxType,
                    'modifyAddr'=>$modifyAddr,
                    'modifyArea'=>$modifyArea,
                    'areaFeeItems'=>$areaFeeItems,
                    'proxy'=>$proxy,
                    'status'=>self::ORDER_STATUS_1,
                    'price'=>(new ExprFee($userType,$taxType,$modifyAddr,$modifyArea,$areaFeeItems,$proxy))->expr(),
                    'tradeType'=>$tradeType,
                ];
                break;
            case 3://渠道
                $insert=[
                    'orderId'=>$this->createOrderId($userType),
                    'phone'=>$phone,
                    'userType'=>$userType,
                    'taxType'=>$taxType,
                    'status'=>self::ORDER_STATUS_1,
                    'price'=>(new ExprFee($userType,$taxType,$modifyAddr,$modifyArea,$areaFeeItems,$proxy))->expr(),
                ];
                break;
        }

        try
        {
            Order::create()->data($insert)->save();

        }catch (\Throwable $e)
        {
            $insert = [];
            CommonService::getInstance()->log4PHP($e->getMessage());
        }

        return $insert;
    }


}
