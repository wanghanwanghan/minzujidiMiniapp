<?php

namespace App\HttpController\Business\Api\User;

use App\HttpController\Business\BusinessBase;
use App\HttpController\Service\OrderService;

class UserController extends BusinessBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    //创建订单
    function createOrder()
    {
        $phone = $this->request()->getRequestParam('phone');
        $taxType = $this->request()->getRequestParam('taxType');
        $modifyAddr = $this->request()->getRequestParam('modifyAddr');
        $modifyArea = $this->request()->getRequestParam('modifyArea');
        $proxy = $this->request()->getRequestParam('proxy');
        $userType = $this->request()->getRequestParam('userType');
        $tradeType = $this->request()->getRequestParam('tradeType');

        $orderInfo = OrderService::getInstance()
            ->createOrder($phone,$userType,$taxType,$modifyAddr,$modifyArea,$proxy,$tradeType);

        return $this->writeJson(200,null,$orderInfo,'成功');
    }
}
