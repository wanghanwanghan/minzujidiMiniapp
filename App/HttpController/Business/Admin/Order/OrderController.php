<?php

namespace App\HttpController\Business\Admin\Order;

use App\HttpController\Business\BusinessBase;
use App\HttpController\Models\Api\EntGuDong;
use App\HttpController\Models\Api\EntInfo;
use App\HttpController\Models\Api\Order;
use App\HttpController\Models\Api\UploadFile;
use App\HttpController\Models\Api\User;
use App\HttpController\Service\OrderService;
use App\HttpController\Service\Pay\wx\wxPayService;
use EasySwoole\Mysqli\QueryBuilder;
use wanghanwanghan\someUtils\control;

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
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 5;

        $list = Order::create();
        $total = Order::create();

        if (!empty($userType)) {
            $list->where('userType', $userType);
            $total->where('userType', $userType);
        }

        $list = $list->order('updated_at', 'desc')
            ->limit($this->exprOffset($page, $pageSize), $pageSize)
            ->all();

        $list = obj2Arr($list);

        $total = $total->count();

        return $this->writeJson(200, $this->createPaging($page, $pageSize, $total), $list);
    }

    //订单退款
    function refundOrder()
    {
        $orderId = $this->request()->getRequestParam('orderId') ?? '';

        $orderInfo = Order::create()->where('orderId', $orderId)->where('status', 3)->get();

        if (empty($orderInfo)) return $this->writeJson(201, null, null, '未发现订单');

        (new wxPayService())->refund($orderId, $orderInfo->finalPrice);

        $orderInfo->update(['status' => 5]);

        return $this->writeJson(200, null, null, '退款成功');
    }

    //订单详情
    function selectDetail()
    {
        $orderId = $this->request()->getRequestParam('orderId') ?? '';

        $orderInfo = Order::create()->where('orderId', $orderId)->get();

        $entInfo = EntInfo::create()->where('orderId', $orderId)->get();

        $guDongInfo = EntGuDong::create()->where('orderId', $orderId)->get();

        $uploadFile = UploadFile::create()->where('orderId', $orderId)->all();

        $info = [
            'orderInfo' => obj2Arr($orderInfo),
            'entInfo' => obj2Arr($entInfo),
            'guDongInfo' => obj2Arr($guDongInfo),
            'uploadFile' => obj2Arr($uploadFile),
        ];

        return $this->writeJson(200, null, $info);
    }

    //更新订单详情
    function updateDetail()
    {
        $orderId = $this->request()->getRequestParam('orderId') ?? '';
        $content = $this->request()->getRequestParam('content') ?? '';

        $check = Order::create()->where('orderId',$orderId)->get();

        if (empty($check)) return $this->writeJson(201, null, null, '未发现订单');

        $content = jsonDecode($content);

        if (empty($content)) return $this->writeJson(201, null, null, '更新内容不能是空');

        $content = control::removeArrKey($content,['created_at','updated_at']);

        //更新订单信息
        if (isset($content['orderInfo']) && !empty($content['orderInfo']))
        {
            Order::create()->where('orderId',$orderId)->update($content['orderInfo']);
        }

        //更新公司信息
        if (isset($content['entInfo']) && !empty($content['entInfo']))
        {
            EntInfo::create()->where('orderId',$orderId)->update($content['entInfo']);
        }

        //更新股东信息
        if (isset($content['guDongInfo']) && !empty($content['guDongInfo']))
        {
            EntGuDong::create()->where('orderId',$orderId)->update($content['guDongInfo']);
        }

        //更新文件上传信息
        if (isset($content['uploadFile']) && !empty($content['uploadFile']))
        {
            foreach ($content['uploadFile'] as $oneFile)
            {
                UploadFile::create()->where(['id'=>$oneFile['id'],'orderId'=>$orderId])->update($oneFile);
            }
        }

        return $this->writeJson(200, null, null,'更新成功');
    }

    //删除订单
    function deleteOrder()
    {
        $orderId = $this->request()->getRequestParam('orderId') ?? '';

        if (empty($orderId)) return $this->writeJson(201, null, null, 'id不能是空');

        Order::create()->destroy(function (QueryBuilder $builder) use ($orderId) {
            $builder->where('orderId', $orderId);
        });

        EntInfo::create()->destroy(function (QueryBuilder $builder) use ($orderId) {
            $builder->where('orderId', $orderId);
        });

        EntGuDong::create()->destroy(function (QueryBuilder $builder) use ($orderId) {
            $builder->where('orderId', $orderId);
        });

        UploadFile::create()->destroy(function (QueryBuilder $builder) use ($orderId) {
            $builder->where('orderId', $orderId);
        });

        return $this->writeJson(200, null, null, '删除成功');
    }

    //创建一个特殊订单
    function createSpecial()
    {
        $phone = $this->request()->getRequestParam('phone');
        $taxType = $this->request()->getRequestParam('taxType');
        $modifyAddr = $this->request()->getRequestParam('modifyAddr');
        $modifyArea = $this->request()->getRequestParam('modifyArea');
        $areaFeeItems = $this->request()->getRequestParam('areaFeeItems');
        $proxy = $this->request()->getRequestParam('proxy');
        $finalPrice = $this->request()->getRequestParam('finalPrice');

        $check = User::create()->where('phone',$phone)->get();

        if (empty($check)) return $this->writeJson(201, null, null, 'phone未注册');

        $orderInfo = OrderService::getInstance()
            ->createSpecial($phone, $check->userType, $taxType, $modifyAddr, $modifyArea, $areaFeeItems, $proxy,$finalPrice);

        return $this->writeJson(200, null, $orderInfo, '成功');
    }
}
