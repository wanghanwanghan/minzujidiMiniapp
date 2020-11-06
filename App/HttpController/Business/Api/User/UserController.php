<?php

namespace App\HttpController\Business\Api\User;

use App\HttpController\Business\BusinessBase;
use App\HttpController\Models\Api\UploadFile;
use App\HttpController\Models\Api\User;
use App\HttpController\Service\CreateTable;
use App\HttpController\Service\OrderService;
use EasySwoole\RedisPool\Redis;

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
        $areaFeeItems = $this->request()->getRequestParam('areaFeeItems');
        $proxy = $this->request()->getRequestParam('proxy');
        $userType = $this->request()->getRequestParam('userType');

        $orderInfo = OrderService::getInstance()
            ->createOrder($phone, $userType, $taxType, $modifyAddr, $modifyArea, $areaFeeItems, $proxy);

        return $this->writeJson(200, null, $orderInfo, '成功');
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

        $filename = explode(',',trim($filename));

        $filename = array_filter($filename);

        $fileNum = count($filename);

        $insert = [
            'orderId' => $orderId,
            'phone' => $phone,
            'type' => $type,
            'fileNum' => $fileNum,
            'filename' => implode(',',$filename),
        ];

        UploadFile::create()->data($insert)->save();

        return $this->writeJson(200,null,$insert,'成功');
    }

    //填写公司信息
    function addEntDetail()
    {
        //     phone = 13800138000，用户手机号
        //     orderId = XXX，用订单号区分是要上传哪个公司的材料
        //     regEntName = XXX，注册公司名，逗号分割
        //     hy = XXX，公司行业
        //     jyfw = XXX，经营范围
        //     gdmc = XXX，股东名称，逗号分割
        //     gdbj = XXX，股东背景
        //     zyyw = XXX，拟主营业务或产品
        //     zczb = XXX，拟注册资本
        //     ztz = XXX，预计总投资
        //     xmnr = XXX，项目内容
        //     tzjgmc = XXX，投资机构名称
        //     tzjgbj = XXX，投资机构背景
        //     tzfx = XXX，投资方向

        $phone = $this->request()->getRequestParam('phone') ?? '';
        $orderId = $this->request()->getRequestParam('orderId') ?? '';
        $regEntName = $this->request()->getRequestParam('regEntName') ?? '';
        $hy = $this->request()->getRequestParam('hy') ?? '';
        $jyfw = $this->request()->getRequestParam('jyfw') ?? '';
        $gdmc = $this->request()->getRequestParam('gdmc') ?? '';
        $gdbj = $this->request()->getRequestParam('gdbj') ?? '';
        $zyyw = $this->request()->getRequestParam('zyyw') ?? '';
        $zczb = $this->request()->getRequestParam('zczb') ?? '';
        $ztz = $this->request()->getRequestParam('ztz') ?? '';
        $xmnr = $this->request()->getRequestParam('xmnr') ?? '';
        $tzjgmc = $this->request()->getRequestParam('tzjgmc') ?? '';
        $tzjgbj = $this->request()->getRequestParam('tzjgbj') ?? '';
        $tzfx = $this->request()->getRequestParam('tzfx') ?? '';
















    }










}
