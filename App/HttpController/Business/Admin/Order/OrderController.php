<?php

namespace App\HttpController\Business\Admin\Order;

use App\HttpController\Business\BusinessBase;
use App\HttpController\Models\Admin\Addr;
use App\HttpController\Models\Admin\AddrUse;
use App\HttpController\Models\Admin\SupervisorPhoneEntName;
use App\HttpController\Models\Api\EntGuDong;
use App\HttpController\Models\Api\EntInfo;
use App\HttpController\Models\Api\Order;
use App\HttpController\Models\Api\UploadFile;
use App\HttpController\Models\Api\User;
use App\HttpController\Service\CommonService;
use App\HttpController\Service\OrderService;
use App\HttpController\Service\Pay\wx\wxPayService;
use Carbon\Carbon;
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

        try
        {
            $res = (new wxPayService())->refund($orderId, $orderInfo->finalPrice);
            CommonService::getInstance()->log4PHP($res,__FUNCTION__);
        }catch (\Throwable $e)
        {
            CommonService::getInstance()->log4PHP($e,__FUNCTION__);
        }

        $orderInfo->update(['status' => 5]);

        return $this->writeJson(200, null, null, '退款成功');
    }

    //订单详情
    function selectDetail()
    {
        $addrId = $this->request()->getRequestParam('addrId') ?? '';
        $orderId = $this->request()->getRequestParam('orderId') ?? '';

        !is_numeric($addrId) ?: $orderId = Addr::create()->get($addrId)->orderId;

        $orderInfo = Order::create()->where('orderId', $orderId)->get();

        $entInfo = EntInfo::create()->where('orderId', $orderId)->get();

        $guDongInfo = EntGuDong::create()->where('orderId', $orderId)->get();

        $uploadFile = UploadFile::create()->where('orderId', $orderId)->all();

        //1是工商，2是税务，3是银行，4是社保，5是公积金
        $orderInfo = obj2Arr($orderInfo);

        for ($i=1;$i<=5;$i++)
        {
            if (empty($orderInfo)) continue;

            if ($i === 1) $orderInfo['areaFeeItems'] = str_replace('1','工商',$orderInfo['areaFeeItems']);
            if ($i === 2) $orderInfo['areaFeeItems'] = str_replace('2','税务',$orderInfo['areaFeeItems']);
            if ($i === 3) $orderInfo['areaFeeItems'] = str_replace('3','银行',$orderInfo['areaFeeItems']);
            if ($i === 4) $orderInfo['areaFeeItems'] = str_replace('4','社保',$orderInfo['areaFeeItems']);
            if ($i === 5) $orderInfo['areaFeeItems'] = str_replace('5','公积金',$orderInfo['areaFeeItems']);
        }

        $orderInfo['taxType'] === 1 ? $orderInfo['taxType'] = '一般纳税人' : $orderInfo['taxType'] = '小规模纳税人';
        $orderInfo['modifyAddr'] === 1 ? $orderInfo['modifyAddr'] = '地址变更' : $orderInfo['modifyAddr'] = '地址不变更';
        $orderInfo['modifyArea'] === 1 ? $orderInfo['modifyArea'] = '跨区' : $orderInfo['modifyArea'] = '不跨区';
        $orderInfo['proxy'] === 1 ? $orderInfo['proxy'] = '代理记账' : $orderInfo['proxy'] = '不代理记账';

        $info = [
            'orderInfo' => $orderInfo,
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
            ->createSpecial($phone, $check->type, $taxType, $modifyAddr, $modifyArea, $areaFeeItems, $proxy,$finalPrice);

        return $this->writeJson(200, null, $orderInfo, '成功');
    }

    //修改审核状态
    function editHandleStatus()
    {
        $orderId = $this->request()->getRequestParam('orderId');
        $handleStatus = $this->request()->getRequestParam('handleStatus');
        $errInfo = $this->request()->getRequestParam('errInfo');

        $info = Order::create()->where('orderId',$orderId)->get();

        if (empty($info)) $this->writeJson(201, null, null, '未找到订单');

        $phone = $info->phone;
        $updated_at = $info->updated_at;
        $created_at = $info->created_at;
        $finalPrice = $info->finalPrice;

        $info->update(['handleStatus'=>$handleStatus,'errInfo'=>$errInfo]);

        //审核失败/成功
        if ($handleStatus == '1' || $handleStatus == '2')
        {
            if ($handleStatus == '1') CommonService::getInstance()->send_shenheshibai([$phone]);

            $phone = Order::create()->where('orderId',$orderId)->get()->phone;
            $openid = User::create()->where('phone',$phone)->get()->openid;

            $ext = [
                'character_string1'=>$orderId,
                'thing2'=>'订单',
                'time4'=>date('Y-m-d H:i:s',$updated_at),
                'phrase6'=>$handleStatus == '1' ? '审核失败' : '审核成功',
            ];

            (new wxPayService())->push_shenhe($openid,$ext);
        }

        //办理成功
        if ($handleStatus == '4')
        {
            CommonService::getInstance()->send_banlichenggong([$phone]);

            $phone = Order::create()->where('orderId',$orderId)->get()->phone;
            $openid = User::create()->where('phone',$phone)->get()->openid;

            $ext = [
                'time2' => date('Y-m-d H:i:s',$updated_at),
                'amount3' => $finalPrice,
                'character_string5' => $orderId,
                'time8' => $created_at,
                'thing4' => '无',
            ];

            (new wxPayService())->push_banli($openid,$ext);
        }

        return $this->writeJson(200, null, null, '成功');
    }

    //修改订单所用的地址
    function editOrderAddr()
    {
        $orderId = $this->request()->getRequestParam('orderId');
        $addrId = $this->request()->getRequestParam('addrId');

        $addInfo = Addr::create()->where('id',$addrId)->where('isUse',0)->get();

        if (empty($addInfo)) return $this->writeJson(201, null, null, '地址已被占用');

        $addInfo->update(['isUse'=>1,'orderId'=>$orderId]);

        AddrUse::create()->data([
            'orderId'=>$orderId,
            'addrId'=>$addrId,
            'startTime'=>time(),
            'endTime'=>Carbon::now()->addYears(1)->timestamp,
        ])->save();

        return $this->writeJson(200, null, null, '修改成功');
    }

    //文件上传
    function adminUploadFile()
    {
        $orderId = $this->request()->getRequestParam('orderId') ?? '';
        $type = $this->request()->getRequestParam('type') ?? '';
        $filename = $this->request()->getRequestParam('filename') ?? '';

        $info = Order::create()->where('orderId',$orderId)->get();

        $info->update(['filePackage'=>$filename]);

        return $this->writeJson(200, null, null, '成功');
    }

    //办理完成后关联公司名称
    function addEntNameToTable()
    {
        $orderId = $this->request()->getRequestParam('orderId') ?? '';
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $code = $this->request()->getRequestParam('code') ?? '';

        if (empty($orderId)) return $this->writeJson(201,null,null,'orderId不能是空');
        if (empty($entName)) return $this->writeJson(201,null,null,'entName不能是空');

        EntInfo::create()->where('orderId',$orderId)->update(['entName'=>$entName,'code'=>$code]);
        Order::create()->where('orderId',$orderId)->update(['entName'=>$entName]);
        UploadFile::create()->where('orderId',$orderId)->update(['entName'=>$entName]);
        UploadFile::create()->where('orderId',$orderId)->update(['entName'=>$entName]);
        $info = SupervisorPhoneEntName::create()->where('entName',$entName)->get();

        !empty($info) ?: SupervisorPhoneEntName::create()->data([
            'phone'=>11111111111,'entName'=>$entName,'status'=>1
        ])->save();

        return $this->writeJson(200,null,null,'成功');
    }












}
