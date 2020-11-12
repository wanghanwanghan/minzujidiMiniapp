<?php

namespace App\HttpController\Business\Api\User;

use App\HttpController\Business\BusinessBase;
use App\HttpController\Models\Api\EntGuDong;
use App\HttpController\Models\Api\EntInfo;
use App\HttpController\Models\Api\Order;
use App\HttpController\Models\Api\User;
use App\HttpController\Service\CreateTable;
use App\HttpController\Service\OrderService;
use App\HttpController\Service\Pay\wx\wxPayService;
use App\HttpController\Service\UploadFile\UploadFileService;
use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\RedisPool\Redis;

class UserController extends BusinessBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    //用户注册
    function userReg()
    {
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $email = $this->request()->getRequestParam('email') ?? '';
        $vCode = $this->request()->getRequestParam('vCode') ?? '';
        $type = $this->request()->getRequestParam('userType');

        if (empty($phone) || empty($vCode)) return $this->writeJson(201, null, null, '手机号或验证码不能是空');

        if (!is_numeric($phone) || !is_numeric($vCode)) return $this->writeJson(201, null, null, '手机号或验证码必须是数字');

        if (strlen($phone) !== 11) return $this->writeJson(201, null, null, '手机号错误');

        $pattern = '/^([0-9A-Za-z\-_\.]+)@([0-9a-z]+\.[a-z]{2,3}(\.[a-z]{2})?)$/i';

        if (!preg_match($pattern, $email)) return $this->writeJson(201, null, null, 'email格式错误');

        $redis = Redis::defer('redis');

        $redis->select(0);

        $vCodeInRedis = $redis->get($phone . 'reg');

        if ((int)$vCodeInRedis !== (int)$vCode) return $this->writeJson(201, null, null, '验证码错误');

        $res = User::create()->where('phone', $phone)->get();

        //已经注册过了
        if ($res) return $this->writeJson(201, null, null, '手机号已经注册过了');

        User::create()->data(['phone' => $phone, 'email' => $email, 'type' => $type])->save();

        return $this->writeJson(200, null, null, '注册成功');
    }

    //用户登录
    function userLogin()
    {
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $userType = $this->request()->getRequestParam('userType') ?? '';
        $vCode = $this->request()->getRequestParam('vCode') ?? '';

        $redis = Redis::defer('redis');

        $redis->select(0);

        $vCodeInRedis = $redis->get($phone . 'login');

        if ((int)$vCodeInRedis !== (int)$vCode) return $this->writeJson(201, null, null, '验证码错误');

        $res = User::create()->where('phone', $phone)->where('type', $userType)->get();

        if (empty($res)) return $this->writeJson(201, null, null, '号码未注册');

        return $this->writeJson(200, null, $res, '登录成功');
    }

    //创建订单
    function createOrder()
    {
        $phone = $this->request()->getRequestParam('phone');
        $taxType = $this->request()->getRequestParam('taxType');
        $modifyAddr = $this->request()->getRequestParam('modifyAddr');
        $modifyArea = $this->request()->getRequestParam('modifyArea');
        $areaFeeItems = $this->request()->getRequestParam('areaFeeItems');
        $proxy = $this->request()->getRequestParam('proxy');
        $userType = $this->request()->getRequestParam('userType');

        $orderInfo = OrderService::getInstance()
            ->createOrder($phone, $userType, $taxType, $modifyAddr, $modifyArea, $areaFeeItems, $proxy);

        return $this->writeJson(200, null, $orderInfo, '成功');
    }

    //支付订单
    function payOrder()
    {
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $jsCode = $this->request()->getRequestParam('jsCode') ?? '';
        $orderId = $this->request()->getRequestParam('orderId') ?? '';

        $info = Order::create()->where('orderId',$orderId)->get();

        //创建小程序支付对象
        $payObj = (new wxPayService())->miniAppPay($jsCode, $orderId, $info->finalPrice,'民族基地');

        return $this->writeJson(200, null, $payObj, '成功');
    }

    //获取订单列表
    function getOrderList()
    {
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $userType = $this->request()->getRequestParam('userType') ?? '';
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $list = Order::create()->where('phone',$phone)->where('userType',$userType)->order('created_at','desc')
            ->limit($this->exprOffset($page,$pageSize),$pageSize)->all();

        $list = json_decode(json_encode($list),true);

        empty($list) ? $list = null : null;

        foreach ($list as &$one)
        {
            switch ($one['status'])
            {
                case '1':
                    $one['statusWord'] = '待确认';
                    break;
                case '2':
                    $one['statusWord'] = '待支付';
                    break;
                case '3':
                    $one['statusWord'] = '支付完成';
                    break;
                case '4':
                    $one['statusWord'] = '支付异常';
                    break;
                case '5':
                    $one['statusWord'] = '已退款';
                    break;
            }

            $one['created_atWord'] = date('Y-m-d H:i:s',$one['created_at']);
        }
        unset($one);

        $total = Order::create()->where('phone',$phone)->where('userType',$userType)->count();

        $page = [
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $total
        ];

        return $this->writeJson(200,$page,$list,'成功');
    }

    //上传文件
    function uploadFile()
    {
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $orderId = $this->request()->getRequestParam('orderId') ?? '';
        $type = $this->request()->getRequestParam('type') ?? '';
        $filename = $this->request()->getRequestParam('filename') ?? '';

        if (empty($phone) || !is_numeric($phone) || strlen($phone) != 11) return $this->writeJson(201,null,null,'手机错误');
        if (empty($orderId)) return $this->writeJson(201,null,null,'订单号错误');
        if (empty($type) || !is_numeric($type) || strlen($type) != 1) return $this->writeJson(201,null,null,'文件类型错误');
        if (empty($filename)) return $this->writeJson(201,null,null,'文件名称错误');

        $res = UploadFileService::getInstance()->uploadFile($filename,$orderId,$phone,$type);

        return $this->writeJson(200,null,$res,'成功');
    }

    //填写公司信息 - 基本信息
    function addEntInfo()
    {
        $orderId = $this->request()->getRequestParam('orderId') ?? '';
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $regEntName = $this->request()->getRequestParam('regEntName') ?? '';//名称
        $hy = $this->request()->getRequestParam('hy') ?? '';//行业
        $jyfw = $this->request()->getRequestParam('jyfw') ?? '';//经营范围
        $zyyw = $this->request()->getRequestParam('zyyw') ?? '';//主营业务
        $zczb = $this->request()->getRequestParam('zczb') ?? '';//注册资本
        $fr = $this->request()->getRequestParam('fr') ?? '';//注册资本
        $frCode = $this->request()->getRequestParam('frCode') ?? '';//身份证号
        $jbr = $this->request()->getRequestParam('jbr') ?? '';//经办人
        $jbrCode = $this->request()->getRequestParam('jbrCode') ?? '';//经办人身份证号

        EntInfo::create()->destroy(function (QueryBuilder $builder) use ($orderId) {
            $builder->where('orderId',$orderId);
        });

        $insert = [
            'orderId' => $orderId,
            'phone' => $phone,
            'regEntName' => $regEntName,
            'hy' => $hy,
            'jyfw' => $jyfw,
            'zyyw' => $zyyw,
            'zczb' => $zczb,
            'fr' => $fr,
            'frCode' => $frCode,
            'jbr' => $jbr,
            'jbrCode' => $jbrCode,
        ];

        EntInfo::create()->data($insert)->save();

        return $this->writeJson(200,null,$insert,'成功');
    }

    //填写公司信息 - 股东信息
    function addEntGuDong()
    {
        $orderId = $this->request()->getRequestParam('orderId') ?? '';
        $content = $this->request()->getRequestParam('content') ?? '';

        //$gdmc = $this->request()->getRequestParam('gdmc') ?? '';//股东名称/公司名称
        //$code = $this->request()->getRequestParam('code') ?? '';//身份证/统一代码
        //$type = $this->request()->getRequestParam('type') ?? '';//投资人类型
        //$cze = $this->request()->getRequestParam('cze') ?? '';//出资额
        //$czfs = $this->request()->getRequestParam('czfs') ?? '';//出资方式
        //$czzb = $this->request()->getRequestParam('czzb') ?? '';//出资占比
        //$czsj = $this->request()->getRequestParam('czsj') ?? '';//出资时间
        //$gdbj = $this->request()->getRequestParam('gdbj') ?? '';//股东背景
        //$csfx = $this->request()->getRequestParam('csfx') ?? '';//从事方向
        //$fr = $this->request()->getRequestParam('fr') ?? '';//法人名称
        //$frCode = $this->request()->getRequestParam('frCode') ?? '';//法人身份证

        EntGuDong::create()->destroy(function (QueryBuilder $builder) use ($orderId) {
            $builder->where('orderId',$orderId);
        });

        foreach (json_decode($content) as $one)
        {
            $insert = [
                'orderId' => $one['orderId'],
                'gdmc' => $one['gdmc'],
                'code' => $one['code'],
                'type' => $one['type'],
                'cze' => $one['cze'],
                'czfs' => $one['czfs'],
                'czzb' => $one['czzb'],
                'czsj' => $one['czsj'],
                'gdbj' => $one['gdbj'],
                'csfx' => $one['csfx'],
                'fr' => $one['fr'],
                'frCode' => $one['frCode'],
            ];

            EntGuDong::create()->data($insert)->save();
        }

        return $this->writeJson(200,null,$insert,'成功');
    }









}
