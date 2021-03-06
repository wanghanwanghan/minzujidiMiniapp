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
    function createOrder($phone,$userType,$taxType,$modifyAddr,$modifyArea,$areaFeeItems,$proxy,$price)
    {
        $userType = (int)$userType;
        $taxType = (int)$taxType;
        $modifyAddr = (int)$modifyAddr;
        $modifyArea = (int)$modifyArea;
        $areaFeeItems = (string)$areaFeeItems;
        $proxy = (int)$proxy;

        $insert=[];

        switch ($userType)
        {
            case 1://会员企业
                $insert=[
                    'orderId'=>$this->createOrderId($userType),
                    'phone'=>$phone,
                    'userType'=>$userType,
                    'taxType'=>$taxType,
                    'modifyAddr'=>$modifyAddr,
                    'modifyArea'=>$modifyArea,
                    'areaFeeItems'=>$areaFeeItems,
                    'proxy'=>$proxy,
                    'status'=>self::ORDER_STATUS_2,
                    'price'=>(new ExprFee($userType,$taxType,$modifyAddr,$modifyArea,$areaFeeItems,$proxy))->expr(),
                    'finalPrice'=>(new ExprFee($userType,$taxType,$modifyAddr,$modifyArea,$areaFeeItems,$proxy))->expr(),
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
                    'status'=>self::ORDER_STATUS_2,
                    'price'=>(new ExprFee($userType,$taxType,$modifyAddr,$modifyArea,$areaFeeItems,$proxy))->expr(),
                    'finalPrice'=>(new ExprFee($userType,$taxType,$modifyAddr,$modifyArea,$areaFeeItems,$proxy))->expr(),
                ];
                break;
            case 3://渠道
                $insert=[
                    'orderId'=>$this->createOrderId($userType),
                    'phone'=>$phone,
                    'userType'=>$userType,
                    'taxType'=>$taxType,
                    'status'=>self::ORDER_STATUS_2,
                    'price'=>(new ExprFee($userType,$taxType,$modifyAddr,$modifyArea,$areaFeeItems,$proxy))->expr(),
                    'finalPrice'=>(new ExprFee($userType,$taxType,$modifyAddr,$modifyArea,$areaFeeItems,$proxy))->expr(),
                ];
                break;
        }

        try
        {
            $money = (new ExprFee($userType,$taxType,$modifyAddr,$modifyArea,$areaFeeItems,$proxy))->expr();

            if ($money <= 0 || !empty($price))
            {
                $insert['status'] = self::ORDER_STATUS_3;
            }

            if (!empty($price)) $insert['finalPrice'] = $price;

            Order::create()->data($insert)->save();

        }catch (\Throwable $e)
        {
            $insert = [];
            CommonService::getInstance()->log4PHP($e->getMessage());
        }

        return $insert;
    }

    //自定义价格订单
    function createSpecial($phone,$userType,$taxType,$modifyAddr,$modifyArea,$areaFeeItems,$proxy,$finalPrice)
    {
        $userType = (int)$userType;
        $taxType = (int)$taxType;
        $modifyAddr = (int)$modifyAddr;
        $modifyArea = (int)$modifyArea;
        $areaFeeItems = (string)$areaFeeItems;
        $proxy = (int)$proxy;

        $insert=[
            'orderId'=>$this->createOrderId($userType),
            'phone'=>$phone,
            'userType'=>$userType,
            'taxType'=>$taxType,
            'modifyAddr'=>$modifyAddr,
            'modifyArea'=>$modifyArea,
            'areaFeeItems'=>$areaFeeItems,
            'proxy'=>$proxy,
            'status'=>self::ORDER_STATUS_2,
            'price'=>$finalPrice,
            'finalPrice'=>$finalPrice,
        ];

        try
        {
            if ($finalPrice <= 0) $insert['status'] = self::ORDER_STATUS_3;

            Order::create()->data($insert)->save();

        }catch (\Throwable $e)
        {
            $insert = [];
            CommonService::getInstance()->log4PHP($e->getMessage());
        }

        return $insert;
    }

}
