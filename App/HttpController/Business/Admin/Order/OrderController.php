<?php

namespace App\HttpController\Business\Admin\Order;

use App\HttpController\Business\BusinessBase;
use App\HttpController\Models\Api\Order;
use App\HttpController\Service\Pay\wx\wxPayService;

class OrderController extends BusinessBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    //获取订单列表
    function selectList()
    {
        $userType = $this->request()->getRequestParam('userType') ?? '';
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $list = Order::create();
        $total = Order::create();

        if (!empty($userType))
        {
            $list->where('userType',$userType);
            $total->where('userType',$userType);
        }

        $list = $list->order('updated_at','desc')
            ->limit($this->exprOffset($page,$pageSize),$pageSize)
            ->all();

        $list = obj2Arr($list);

        $total = $total->count();

        return $this->writeJson(200,$this->createPaging($page,$pageSize,$total),$list);
    }

    //订单退款
    function refundOrder()
    {
        $orderId = $this->request()->getRequestParam('orderId') ?? '';

        return $this->writeJson(200,null,(new wxPayService())->refund($orderId,123));
    }




}
