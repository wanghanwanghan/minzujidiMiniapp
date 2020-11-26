<?php

namespace App\HttpController\Business\Admin\Order;

use App\HttpController\Business\BusinessBase;
use App\HttpController\Models\Api\Order;

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

        if (!empty($userType))
        {
            $list->where('userType',$userType);
        }

        $list = $list->withTotalCount()
            ->order('updated_at','desc')
            ->limit($this->exprOffset($page,$pageSize),$pageSize)
            ->all();

        return $this->writeJson(200,null,$list);
    }





}
