<?php

namespace App\HttpController\Business\Api\User;

use App\HttpController\Business\BusinessBase;
use App\HttpController\Models\Admin\Addr;
use App\HttpController\Models\Api\EntGuDong;
use App\HttpController\Models\Api\EntInfo;
use App\HttpController\Models\Api\Order;
use App\HttpController\Models\Api\UploadFile;
use App\HttpController\Models\Api\User;
use App\HttpController\Service\CommonService;
use App\HttpController\Service\CreateTable;
use App\HttpController\Service\OrderService;
use App\HttpController\Service\Pay\wx\wxPayService;
use App\HttpController\Service\UploadFile\UploadFileService;
use App\Task\Docx2pdf;
use Carbon\Carbon;
use EasySwoole\EasySwoole\Task\TaskManager;
use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\DbManager;
use EasySwoole\RedisPool\Redis;
use PhpOffice\PhpWord\TemplateProcessor;

class UserController extends BusinessBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    //后台创建订单的时候自动注册
    function autoReg()
    {
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $password = $this->request()->getRequestParam('password') ?? '11111111';
        $email = $this->request()->getRequestParam('email') ?? '';
        $type = $this->request()->getRequestParam('userType');

        $pattern = '/^([0-9A-Za-z\-_\.]+)@([0-9a-z]+\.[a-z]{2,3}(\.[a-z]{2})?)$/i';
        if (!preg_match($pattern, $email)) return $this->writeJson(201, null, null, 'email格式错误');

        if (!preg_match('/^[0-9a-zA-Z\_]{8,20}$/',$password))
            return $this->writeJson(201, null, null, '密码只能是8-20位的字母数字下划线组合');

        $check = User::create()->where('phone',$phone)->get();

        if (!empty($check)) return $this->writeJson(201, null, $check, '手机号已注册');

        User::create()->data([
            'phone' => $phone,
            'password' => $password,
            'email' => $email,
            'type' => $type
        ])->save();

        $check = User::create()->where('phone',$phone)->get();

        return $this->writeJson(200, null, $check, '注册成功');
    }

    //用户注册
    function userReg()
    {
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $password = $this->request()->getRequestParam('password') ?? '';
        $email = $this->request()->getRequestParam('email') ?? '';
        $vCode = $this->request()->getRequestParam('vCode') ?? '';
        $type = $this->request()->getRequestParam('userType');

        if (empty($phone) || empty($vCode)) return $this->writeJson(201, null, null, '手机号或验证码不能是空');

        if (!is_numeric($phone) || !is_numeric($vCode)) return $this->writeJson(201, null, null, '手机号或验证码必须是数字');

        if (strlen($phone) !== 11) return $this->writeJson(201, null, null, '手机号错误');

        $pattern = '/^([0-9A-Za-z\-_\.]+)@([0-9a-z]+\.[a-z]{2,3}(\.[a-z]{2})?)$/i';
        if (!preg_match($pattern, $email)) return $this->writeJson(201, null, null, 'email格式错误');

        if (!preg_match('/^[0-9a-zA-Z\_]{8,20}$/',$password))
            return $this->writeJson(201, null, null, '密码只能是8-20位的字母数字下划线组合');

        $redis = Redis::defer('redis');

        $redis->select(0);

        $vCodeInRedis = $redis->get($phone . 'reg');

        if ((int)$vCodeInRedis !== (int)$vCode) return $this->writeJson(201, null, null, '验证码错误');

        $res = User::create()->where('phone', $phone)->get();

        //已经注册过了
        if ($res) return $this->writeJson(201, null, null, '手机号已经注册过了');

        User::create()->data([
            'phone' => $phone,
            'password' => $password,
            'email' => $email,
            'type' => $type
        ])->save();

        return $this->writeJson(200, null, null, '注册成功');
    }

    //用户登录
    function userLogin()
    {
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $password = $this->request()->getRequestParam('password') ?? '';
        $userType = $this->request()->getRequestParam('userType') ?? '';
        $vCode = $this->request()->getRequestParam('vCode') ?? '';

        $res = User::create()->where('phone', $phone)->where('type', $userType)->get();

        if (empty($res)) return $this->writeJson(201, null, null, '号码未注册');

        if (!empty($password))
        {
            $res = User::create()
                ->where('phone', $phone)
                ->where('password', $password)
                ->where('type', $userType)->get();
            if (empty($res)) return $this->writeJson(201, null, null, '密码错误');
        }else
        {
            $redis = Redis::defer('redis');
            $redis->select(0);
            $vCodeInRedis = $redis->get($phone . 'login');
            if ((int)$vCodeInRedis !== (int)$vCode) return $this->writeJson(201, null, null, '验证码错误');
            $res = User::create()->where('phone', $phone)->where('type', $userType)->get();
        }

        return $this->writeJson(200, null, $res, '登录成功');
    }

    //修改密码
    function editPassword()
    {
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $password = $this->request()->getRequestParam('password') ?? '';
        $userType = $this->request()->getRequestParam('userType') ?? '';

        if (empty($phone)) return $this->writeJson(201, null, null, '手机号不能是空');
        if (empty($password)) return $this->writeJson(201, null, null, '密码不能是空');
        if (empty($userType)) return $this->writeJson(201, null, null, 'userType不能是空');

        if (!preg_match('/^[0-9a-zA-Z\_]{8,20}$/',$password))
            return $this->writeJson(201, null, null, '密码只能是8-20位的字母数字下划线组合');

        $userInfo = User::create()->where(['phone'=>$phone, 'type'=>$userType,])->get();
        if (empty($userInfo)) return $this->writeJson(201, null, null, '手机不存在');

        $userInfo->update(['password'=>$password]);

        return $this->writeJson(200, null, null, '修改成功');
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
        $price = $this->request()->getRequestParam('price') ?? '';

        $orderInfo = OrderService::getInstance()
            ->createOrder($phone, $userType, $taxType, $modifyAddr, $modifyArea, $areaFeeItems, $proxy, $price);

        return $this->writeJson(200, null, $orderInfo, '成功');
    }

    //支付订单
    function payOrder()
    {
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $jsCode = $this->request()->getRequestParam('jsCode') ?? '';
        $orderId = $this->request()->getRequestParam('orderId') ?? '';

        $info = Order::create()->where('orderId', $orderId)->get();

        //创建小程序支付对象
        $payObj = (new wxPayService())->miniAppPay($jsCode, $orderId, $info->finalPrice, '民族基地');

        //新企业提交
        if (Carbon::now()->format('Y-m-d') != '2021-01-07') CommonService::getInstance()->send_xinqiyetijiao();

        return $this->writeJson(200, null, $payObj, '成功');
    }

    //删除订单
    function deleteOrder()
    {
        $orderId = $this->request()->getRequestParam('orderId') ?? '';
        $phone = $this->request()->getRequestParam('phone') ?? '';

        $orderInfo = Order::create()->where([
            'orderId' => $orderId,
            'phone' => $phone,
            'status' => 3,
        ])->where('finalPrice',0,'<>')->get();

        if (!empty($orderInfo)) return $this->writeJson(201, null, null, '已支付订单不可删除');

        $orderInfo = Order::create()->where([
            'orderId' => $orderId,
            'phone' => $phone,
        ])->where('handleStatus',0,'<>')->get();

        if (!empty($orderInfo)) return $this->writeJson(201, null, null, '办理中订单不可删除');

        $orderInfo = Order::create()->where([
            'orderId' => $orderId,
            'phone' => $phone,
        ])->get();

        if (empty($orderInfo)) return $this->writeJson(201, null, null, '订单不存在');

        Order::create()->destroy(function (QueryBuilder $builder) use ($orderId) {
            $builder->where('orderId',$orderId);
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

    //获取订单列表
    function getOrderList()
    {
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $userType = $this->request()->getRequestParam('userType') ?? '';
        $hasEntName = $this->request()->getRequestParam('hasEntName') ?? 1;
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $hasEntName == 1 ? $hasEntName = 'not' : $hasEntName = '';

        $list = Order::create()->alias('t1')->join('miniapp_ent_info as t2','t1.orderId = t2.orderId','left')
            ->field(['t1.*'])
            ->where('t1.phone', $phone)
            ->where("t2.regEntName is {$hasEntName} null")
            ->where('t1.userType', $userType)
            ->order('t1.created_at', 'desc')
            ->limit($this->exprOffset($page, $pageSize), $pageSize)
            ->all();

        $list = json_decode(json_encode($list), true);

        empty($list) ? $list = null : null;

        foreach ($list as &$one) {

            switch ($one['status']) {
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

            $one['regEntName'] = EntInfo::create()->field('regEntName')->where('orderId',$one['orderId'])->get();

            $one['created_atWord'] = date('Y-m-d H:i:s', $one['created_at']);
        }
        unset($one);

        $total = Order::create()->where('phone', $phone)->where('userType', $userType)->count();

        $page = [
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $total
        ];

        return $this->writeJson(200, $page, $list, '成功');
    }

    //上传文件
    function uploadFile()
    {
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $orderId = $this->request()->getRequestParam('orderId') ?? '';
        $type = $this->request()->getRequestParam('type') ?? '';
        $isc = $this->request()->getRequestParam('isc') ?? 0;
        $filename = $this->request()->getRequestParam('filename') ?? '';
        $startTime = $this->request()->getRequestParam('startTime') ?? 0;
        $endTime = $this->request()->getRequestParam('endTime') ?? 0;

        $isc = (int)$isc;

        if (empty($type)) return $this->writeJson(201, null, null, '文件类型错误');
        if (empty($orderId)) return $this->writeJson(201, null, null, '订单号错误');

        if ($type !== '4-1')
        {
            if (empty($filename)) return $this->writeJson(201, null, null, '文件名称错误');
            if (empty($phone) || !is_numeric($phone) || strlen($phone) != 11) return $this->writeJson(201, null, null, '手机错误');
        }

        $type == 6 ? $status = 1 : $status = 0;

        if ($type == 5) CommonService::getInstance()->send_xxbtz();
        if ($type == 6) CommonService::getInstance()->send_xytz();

        $res = UploadFileService::getInstance()
            ->uploadFile($filename, $orderId, $phone, $type, $status, $startTime, $endTime, $isc);

        return $this->writeJson(200, null, $res, '成功');
    }

    //获取上传文件列表
    function uploadFileList()
    {
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $orderId = $this->request()->getRequestParam('orderId') ?? '';

        if (empty($phone) || !is_numeric($phone) || strlen($phone) != 11) return $this->writeJson(201, null, null, '手机错误');
        if (empty($orderId)) return $this->writeJson(201, null, null, '订单号错误');

        $res = UploadFile::create()->where('phone', $phone)->where('orderId', $orderId)->all();

        $res = json_decode(json_encode($res), true);

        if (!empty($res))
        {
            foreach ($res as &$one)
            {
                switch ($one['status'])
                {
                    case 0:
                        $one['statusWord'] = '已上传';
                        break;
                    case 1:
                        $one['statusWord'] = '等待确认';
                        break;
                    case 2:
                        $one['statusWord'] = '等您确认';
                        break;
                }
                $one['created_atWord'] = date('Y-m-d H:i:s',$one['updated_at']);
            }
            unset($one);
        }

        //需不需要核名
        $entInfo = EntInfo::create()->where('orderId',$orderId)->get();
        $paging['regEntName'] = strpos($entInfo->regEntName,',') !== false ? 1 : 0;

        //后台审核通过后才让下载协议和信息表
        $fileInfo = Order::create()->where('orderId',$orderId)->where('handleStatus',1,'>=')->get();
        $paging['downloadStatus'] = !empty($fileInfo) ? 1 : 0;

        return $this->writeJson(200, $paging, $res, '成功');
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
        $frPhone = $this->request()->getRequestParam('frPhone') ?? '';//手机
        $frTel = $this->request()->getRequestParam('frTel') ?? '';//座机
        $frAddr = $this->request()->getRequestParam('frAddr') ?? '';//地址
        $frEmail = $this->request()->getRequestParam('frEmail') ?? '';//邮箱
        $jbr = $this->request()->getRequestParam('jbr') ?? '';//经办人
        $jbrCode = $this->request()->getRequestParam('jbrCode') ?? '';//经办人身份证号
        $jbrPhone = $this->request()->getRequestParam('jbrPhone') ?? '';//经办人手机
        $jbrTel = $this->request()->getRequestParam('jbrTel') ?? '';//经办人座机
        $jbrAddr = $this->request()->getRequestParam('jbrAddr') ?? '';//经办人地址
        $jbrEmail = $this->request()->getRequestParam('jbrEmail') ?? '';//经办人邮箱
        $zs = $this->request()->getRequestParam('zs') ?? '';//住所
        $code = $this->request()->getRequestParam('code') ?? '';//统一社会信用代码

        EntInfo::create()->destroy(function (QueryBuilder $builder) use ($orderId) {
            $builder->where('orderId', $orderId);
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
            'frPhone' => $frPhone,
            'frTel' => $frTel,
            'frAddr' => $frAddr,
            'frEmail' => $frEmail,
            'jbr' => $jbr,
            'jbrCode' => $jbrCode,
            'jbrPhone' => $jbrPhone,
            'jbrTel' => $jbrTel,
            'jbrAddr' => $jbrAddr,
            'jbrEmail' => $jbrEmail,
            'zs' => $zs,
            'code' => $code,
        ];

        if (count(explode(',',$regEntName)) <= 1)
        {
            $orderInfo = Order::create()->where('orderId',$orderId)->get();
            $orderInfo->update([
                'entName' => $regEntName,
            ]);
            $insert['entName'] = $regEntName;
        }

        EntInfo::create()->data($insert)->save();

        return $this->writeJson(200, null, $insert, '成功');
    }

    //填写公司信息 - 股东信息
    function addEntGuDong()
    {
        $orderId = $this->request()->getRequestParam('orderId') ?? '';
        $gdmc = $this->request()->getRequestParam('gdmc') ?? '';//股东名称/公司名称
        $code = $this->request()->getRequestParam('code') ?? '';//身份证/统一代码
        $type = $this->request()->getRequestParam('type') ?? '';//投资人类型
        $cze = $this->request()->getRequestParam('cze') ?? '';//出资额
        $czfs = $this->request()->getRequestParam('czfs') ?? '';//出资方式
        $czzb = $this->request()->getRequestParam('czzb') ?? '';//出资占比
        $czsj = $this->request()->getRequestParam('czsj') ?? '';//出资时间
        $gdbj = $this->request()->getRequestParam('gdbj') ?? '';//股东背景
        $csfx = $this->request()->getRequestParam('csfx') ?? '';//从事方向
        $fr = $this->request()->getRequestParam('fr') ?? '';//法人名称
        $frCode = $this->request()->getRequestParam('frCode') ?? '';//法人身份证
        $image = $this->request()->getRequestParam('image') ?? '';//照片
        $random = $this->request()->getRequestParam('random') ?? '';//随机数

        EntGuDong::create()->destroy(function (QueryBuilder $builder) use ($orderId, $random) {
            $builder->where('orderId', $orderId)->where('random', $random, '<>');
        });

        $insert = [
            'orderId' => $orderId,
            'gdmc' => $gdmc,
            'code' => $code,
            'type' => $type,
            'cze' => $cze,
            'czfs' => $czfs,
            'czzb' => $czzb,
            'czsj' => $czsj,
            'gdbj' => $gdbj,
            'csfx' => $csfx,
            'fr' => $fr,
            'frCode' => $frCode,
            'image' => $image,
            'random' => $random,
        ];

        EntGuDong::create()->data($insert)->save();

        return $this->writeJson(200, null, $insert, '成功');
    }

    //下载文件
    function downloadFile()
    {
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $orderId = $this->request()->getRequestParam('orderId') ?? '';
        $downloadType = $this->request()->getRequestParam('downloadType') ?? 1;
        $fileType = $this->request()->getRequestParam('fileType') ?? '';

        $user = User::create()->where('phone',$phone)->get();

        $email = $user->email;

        $subject = '民族基地';

        switch ($fileType)
        {
            case '5':
                //企业设立登记住所承诺书
                $subject = '民孵企服 - 企业设立登记住所承诺书';
                $docxObj = new TemplateProcessor(STATIC_PATH . 'xinxibiao.docx');
                $entInfo = EntInfo::create()->where('orderId',$orderId)->get();
                //公司名称
                $entName = $entInfo->entName;
//                $tradeType = $entInfo->hy;
//                $regMoney = $entInfo->zczb;
                $fr = $entInfo->fr;
//                $frCode = $entInfo->frCode;
//                $frPhone = $entInfo->frPhone;
//                $frTel = $entInfo->frTel;
//                $frAddr = $entInfo->frAddr;
//                $frEmail = $entInfo->frEmail;
//                $jbr = $entInfo->jbr;
//                $jbrCode = $entInfo->jbrCode;
//                $jbrPhone = $entInfo->jbrPhone;
//                $jbrTel = $entInfo->jbrTel;
//                $jbrAddr = $entInfo->jbrAddr;
//                $jbrEmail = $entInfo->jbrEmail;

                $docxObj->setValue('entName',$entName);
//                $docxObj->setValue('tradeType',$tradeType);
//                $docxObj->setValue('regMoney',$regMoney);
                $docxObj->setValue('fr',$fr);
//                $docxObj->setValue('frCode',$frCode);
//                $docxObj->setValue('frPhone',$frPhone);
//                $docxObj->setValue('frTel',$frTel);
//                $docxObj->setValue('frAddr',$frAddr);
//                $docxObj->setValue('frEmail',$frEmail);
//                $docxObj->setValue('jbr',$jbr);
//                $docxObj->setValue('jbrCode',$jbrCode);
//                $docxObj->setValue('jbrPhone',$jbrPhone);
//                $docxObj->setValue('jbrTel',$jbrTel);
//                $docxObj->setValue('jbrAddr',$jbrAddr);
//                $docxObj->setValue('jbrEmail',$jbrEmail);

                //签字盖章
                $docxObj->saveAs(FILE_PATH . $orderId . '.docx');
                $file = FILE_PATH . $orderId . '.docx';

                break;
            case '6':
                //企业设立登记住所管理协议
                $subject = '民孵企服 - 企业设立登记住所管理协议';
                $docxObj = new TemplateProcessor(STATIC_PATH . 'xieyi.docx');
                $entInfo = EntInfo::create()->where('orderId',$orderId)->get();
                //公司名称
                $entName = $entInfo->entName;
                $entAddr = EntInfo::create()->where('orderId',$orderId)->get()->zs;
                $tradeType = $entInfo->hy;
                $regMoney = $entInfo->zczb;
                $code = $entInfo->code;
                $fr = $entInfo->fr;
                $frAddr = $entInfo->frAddr;
                $frCode = $entInfo->frCode;
                $frPhone = $entInfo->frPhone;
                $finalPrice = Order::create()->where('orderId',$orderId)->get()->finalPrice;
                $finalPriceChinese = CommonService::getInstance()->toChineseNumber($finalPrice);

                $timeInfo = UploadFile::create()->where('orderId',$orderId)->where('type',4)->get();

                if (empty($timeInfo))
                {
                    $sYear = Carbon::now()->year;
                    $sMonth = Carbon::now()->month;
                    $sDay = Carbon::now()->day;
                    $eYear = Carbon::now()->addDays(365)->year;
                    $eMonth = Carbon::now()->addDays(365)->month;
                    $eDay = Carbon::now()->addDays(364)->day;
                }else
                {
                    $sTime = $timeInfo->startTime;
                    $eTime = $timeInfo->endTime;

                    $sYear = date('Y',$sTime);
                    $sMonth = date('m',$sTime);
                    $sDay = date('d',$sTime);
                    $eYear = date('Y',$eTime);
                    $eMonth = date('m',$eTime);
                    $eDay = date('d',$eTime);
                }

                $docxObj->setValue('entName',$entName);
                $docxObj->setValue('entAddr',$entAddr);
                //$docxObj->setValue('tradeType',$tradeType);
                $docxObj->setValue('regMoney',$regMoney);
                $docxObj->setValue('code',$code);
                $docxObj->setValue('fr',$fr);
                $docxObj->setValue('frAddr',$frAddr);
                $docxObj->setValue('frCode',$frCode);
                $docxObj->setValue('frPhone',$frPhone);
                $docxObj->setValue('sYear',$sYear);
                $docxObj->setValue('sMon',$sMonth);
                $docxObj->setValue('sDay',$sDay);
                $docxObj->setValue('eYear',$eYear);
                $docxObj->setValue('eMon',$eMonth);
                $docxObj->setValue('eDay',$eDay);
                $docxObj->setValue('finalPrice',$finalPrice);
                $docxObj->setValue('finalPriceChinese',$finalPriceChinese);

                //签字盖章!
                $docxObj->setImageValue('zhang', [
                    'path' => STATIC_PATH . 'mzjd_zhang_one.png',
                    'width' => 430,
                    'height' => 180
                ]);
                $docxObj->saveAs(FILE_PATH . $orderId . '.docx');
                $file = FILE_PATH . $orderId . '.docx';

                break;
            case '99':
                $subject = '民孵企服 - 企业设立登记全部资料';
                $file = FILE_PATH . Order::create()->where('orderId',$orderId)->get()->filePackage;
                break;
            default:
                $file = null;
        }

        $sendFile = [$file];

        //docx2pdf
        $sendFile = TaskManager::getInstance()->sync(new Docx2pdf($sendFile));

        $downloadType != 1 ?: CommonService::getInstance()->sendEmail($email,$sendFile,$subject);

        return $this->writeJson(200,null,$sendFile,'成功');
    }

    //办理状态
    function entStatus()
    {
        $orderId = $this->request()->getRequestParam('orderId') ?? '';

        $entInfo = EntInfo::create()->where('orderId',$orderId)->get();

        $entName = $entInfo->entName;
        $regEntName = $entInfo->regEntName;

        if (!empty($entName))
        {
            $entName = [$entName];
        }else
        {
            $entName = explode(',',$regEntName);

            !is_string($entName) ?: $entName = [$entName];
        }

        $orderInfo = Order::create()->where('orderId',$orderId)->get();

        $handleStatus = $orderInfo->handleStatus;

        $handleTime = date('Y-m-d H:i:s',$orderInfo->updated_at);

        switch ($handleStatus)
        {
            case '0':
                $handleStatus = '审核中';
                break;
            case '1':
                $handleStatus = '审核失败';
                break;
            case '2':
                $handleStatus = '正在办理';
                break;
            case '3':
                $handleStatus = '办理失败';
                break;
            case '4':
                $handleStatus = '办理成功';
                break;
            default:
                $handleStatus = '正在办理';
        }

        $fileType1 = UploadFile::create()->where('orderId',$orderId)->where('type',1)->get();

        $res['entName'] = $entName;
        $res['fileType1'] = empty($fileType1) ? 0 : 1;
        $res['handleStatus'] = $handleStatus;
        $res['handleTime'] = $handleTime;
        $res['errInfo'] = $orderInfo->errInfo;
        $cond1 = count(explode('协议',$orderInfo->errInfo)) >= 2;
        $cond2 = count(explode('承诺',$orderInfo->errInfo)) >= 2;
        ($cond1 || $cond2) ? $res['redirect'] = '协议或承诺书' : $res['redirect'] = '其他';
        $res['filePackage'] = $orderInfo->filePackage;

        return $this->writeJson(200,null,$res,'成功');
    }

    //上传文件页面
    function uploadFilePage()
    {
        $orderId = $this->request()->getRequestParam('orderId') ?? '';
        $phone = $this->request()->getRequestParam('phone') ?? '';








        return $this->writeJson(200,null,[],'成功');
    }

}
