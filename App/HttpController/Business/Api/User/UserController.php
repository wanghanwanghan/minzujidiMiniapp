<?php

namespace App\HttpController\Business\Api\User;

use App\HttpController\Business\BusinessBase;
use App\HttpController\Service\CreateTable;
use App\HttpController\Service\ExprFee;
use wanghanwanghan\someUtils\control;

class UserController extends BusinessBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    //创建订单
    function createOrder()
    {
        $taxType = $this->request()->getRequestParam('taxType');
        $modifyAddr = $this->request()->getRequestParam('modifyAddr');
        $modifyArea = $this->request()->getRequestParam('modifyArea');
        $proxy = $this->request()->getRequestParam('proxy');
        $userType = $this->request()->getRequestParam('userType');

        //需要付费的价格
        $fee = (new ExprFee($userType,$taxType,$modifyAddr,$modifyArea,$proxy))->expr();

        //创建订单


    }
}
