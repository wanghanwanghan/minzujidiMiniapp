<?php

namespace App\HttpController\Business\Admin\Addr;

use App\HttpController\Business\BusinessBase;
use App\HttpController\Models\Admin\Addr;
use App\HttpController\Models\Admin\AddrUse;
use App\HttpController\Models\Api\EntGuDong;
use App\HttpController\Models\Api\EntInfo;
use App\HttpController\Models\Api\Order;
use App\HttpController\Service\CommonService;
use App\HttpController\Service\CreateTable;
use Carbon\Carbon;
use EasySwoole\Http\Message\UploadFile;
use EasySwoole\ORM\DbManager;
use EasySwoole\RedisPool\Redis;
use wanghanwanghan\someUtils\control;

class AddrController extends BusinessBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    //导入地址文件
    function insertAddr()
    {
        $addrFile = current($this->request()->getUploadedFiles());

        if ($addrFile instanceof UploadFile)
        {
            //提取文件后缀
            $ext = explode('.', $addrFile->getClientFilename());
            $ext = end($ext);

            if ($ext !== 'xlsx') return $this->writeJson(201,null,null,'文件格式错误');

            //新建文件名
            $filename = date('YmdHis') . '.' . $ext;

            //移动到文件夹
            $addrFile->moveTo(FILE_PATH . $filename);

            //处理文件
            $config = ['path' => FILE_PATH];

            $excel = new \Vtiful\Kernel\Excel($config);

            $excel->openFile($filename)->openSheet();

            $i = 0;

            while (true)
            {
                $i++;

                $one = $excel->nextRow([
                    \Vtiful\Kernel\Excel::TYPE_STRING,
                    \Vtiful\Kernel\Excel::TYPE_STRING,
                    \Vtiful\Kernel\Excel::TYPE_STRING,
                ]);

                if (!$one) break;

                //第一行是表头
                if ($i === 1) continue;

                //处理数据
                $one[0] = trim($one[0]);
                $one[1] = trim($one[1]);
                $one[2] = trim($one[2]);

                $check = Addr::create()->where('category',$one[0])->where('number',$one[1])->get();

                if (!empty($check)) continue;

                Addr::create()->data([
                    'category' => $one[0],
                    'number' => $one[1],
                    'name' => $one[2],
                ])->save();
            }

            return $this->writeJson(200,null,null,'处理成功');
        }

        return $this->writeJson(201,null,null,'未发现上传文件');
    }

    //修改地址
    function editAddr()
    {
        $content = $this->request()->getRequestParam('content') ?? '';
        $type = $this->request()->getRequestParam('type') ?? '';

        $content = jsonDecode($content);

        if (empty($content)) return $this->writeJson(201,null,null,'错误');

        $id = $content['id'];

        $content = control::removeArrKey($content,['id','created_at','updated_at']);

        if ($type == 1)
        {
            Addr::create()->where('id',$id)->update($content);
        }elseif ($type == 2)
        {
            AddrUse::create()->where('id',$id)->update($content);
        }else
        {
            $wanghan = null;
        }

        return $this->writeJson(200,null,null,'成功');
    }

    //获取地址列表/详情
    function selectList()
    {
        $keyword = $this->request()->getRequestParam('keyword') ?? '';//搜公司名称，地址名称，法人
        $cond = $this->request()->getRequestParam('cond') ?? '';//开业-地址异常-吊销-注销-地址变更-30天内到期
        $cond = str_replace(['[',']','"'],'',$cond);
        $cond = explode(',',$cond);
        $cond = array_filter($cond);
        $export = $this->request()->getRequestParam('export') ?? '';
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 5;

        //
        $list = EntInfo::create()->alias('ent')->field([
            'orderTable.orderId',
            'ent.code',
            'ent.entName',
            'ent.regEntName',
            'ent.entStatusInApi',
            'ent.entAddrInApi',
            'ent.addr',
            'ent.fr',
            'ent.frPhone',
            'ent.jbr',
            'ent.jbrPhone',
            'orderTable.finalPrice',
            'ent.fileNumber',
            'ent.applyEnt',
            'ent.managementPrice',
            'ent.receivableManagementPrice',
        ])->join('miniapp_order as orderTable','ent.orderId = orderTable.orderId','left')
            ->join('miniapp_upload_file as uploadTable','ent.orderId = uploadTable.orderId','left')
            ->where('uploadTable.type',4);

        if (!empty($keyword))
        {
            $list->where("ent.entName like '%{$keyword}%' or ent.addr like '%{$keyword}%' or ent.fr like '%{$keyword}%'");
        }

        if (!empty($cond))
        {
            $sql = '';

            if (in_array('开业',$cond)) $sql .= 'ent.entStatusInApi like "%开业%" or ';
            if (in_array('地址异常',$cond)) $sql .= 'ent.entStatusInApi like "%地址%" or ';
            if (in_array('吊销',$cond)) $sql .= 'ent.entStatusInApi like "%吊销%" or ';
            if (in_array('注销',$cond)) $sql .= 'ent.entStatusInApi like "%注销%" or ';
            if (in_array('地址变更',$cond)) $sql .= '(ent.entAddrInApi not like "%民族园%" and ent.entAddrInApi <> "") or ';
            if (in_array('30天内到期',$cond)) $sql .= 'uploadTable.endTime < '.Carbon::now()->addDays(30)->timestamp.' or ';

            $sql = trim($sql);
            $sql = trim($sql,'or');

            empty($sql) ?: $list->where("({$sql})");
        }

        $list = $list->limit($this->exprOffset($page, $pageSize), $pageSize)->all();

        // CommonService::getInstance()->log4PHP(DbManager::getInstance()->getLastQuery()->getLastQuery());

        $list = obj2Arr($list);

        //协议和住所是否到期
        foreach ($list as $key => $oneEnt)
        {
            $list[$key]['xyIsExpire'] = $list[$key]['zlhtIsExpire'] = '未过期';

            $xyInfo = \App\HttpController\Models\Api\UploadFile::create()->where([
                'orderId' => $oneEnt['orderId'],
                'type' => 6
            ])->get();

            $zlhtInfo = \App\HttpController\Models\Api\UploadFile::create()->where([
                'orderId' => $oneEnt['orderId'],
                'type' => 4
            ])->get();

            $now = Carbon::now()->timestamp;

            if (!empty($xyInfo) && $xyInfo->endTime < $now) $list[$key]['xyIsExpire'] = '已经过期';
            if (!empty($zlhtInfo) && $zlhtInfo->endTime < $now) $list[$key]['zlhtIsExpire'] = '已经过期';
        }

        //
        $total = EntInfo::create()->alias('ent')->field([
            'ent.id',
        ])->join('miniapp_order as orderTable','ent.orderId = orderTable.orderId','left')
            ->join('miniapp_upload_file as uploadTable','ent.orderId = uploadTable.orderId','left')
            ->where('uploadTable.type',4);

        if (!empty($keyword))
        {
            $total->where("ent.entName like '%{$keyword}%' or ent.addr like '%{$keyword}%' or ent.fr like '%{$keyword}%'");
        }

        if (!empty($cond))
        {
            $sql = '';

            if (in_array('开业',$cond)) $sql .= 'ent.entStatusInApi like "%开业%" or ';
            if (in_array('地址异常',$cond)) $sql .= 'ent.entStatusInApi like "%地址%" or ';
            if (in_array('吊销',$cond)) $sql .= 'ent.entStatusInApi like "%吊销%" or ';
            if (in_array('注销',$cond)) $sql .= 'ent.entStatusInApi like "%注销%" or ';
            if (in_array('地址变更',$cond)) $sql .= '(ent.entAddrInApi not like "%民族园%" and ent.entAddrInApi <> "") or ';
            if (in_array('30天内到期',$cond)) $sql .= 'uploadTable.endTime < '.Carbon::now()->addDays(30)->timestamp.' or ';

            $sql = trim($sql);
            $sql = trim($sql,'or');

            empty($sql) ?: $total->where("({$sql})");
        }

        $total = $total->count('ent.id');

        //
        if ((int)$export === 1)
        {
            $entList = EntInfo::create()->alias('ent')->field([
                'ent.entName',
            ])->join('miniapp_order as orderTable','ent.orderId = orderTable.orderId','left')
                ->join('miniapp_upload_file as uploadTable','ent.orderId = uploadTable.orderId','left')
                ->where('uploadTable.type',4);

            if (!empty($keyword))
            {
                $entList->where("ent.entName like '%{$keyword}%' or ent.addr like '%{$keyword}%' or ent.fr like '%{$keyword}%'");
            }

            if (!empty($cond))
            {
                $sql = '';

                if (in_array('开业',$cond)) $sql .= 'ent.entStatusInApi like "%开业%" or ';
                if (in_array('地址异常',$cond)) $sql .= 'ent.entStatusInApi like "%地址%" or ';
                if (in_array('吊销',$cond)) $sql .= 'ent.entStatusInApi like "%吊销%" or ';
                if (in_array('注销',$cond)) $sql .= 'ent.entStatusInApi like "%注销%" or ';
                if (in_array('地址变更',$cond)) $sql .= '(ent.entAddrInApi not like "%民族园%" and ent.entAddrInApi <> "") or ';
                if (in_array('30天内到期',$cond)) $sql .= 'uploadTable.endTime < '.Carbon::now()->addDays(30)->timestamp.' or ';

                $sql = trim($sql);
                $sql = trim($sql,'or');

                empty($sql) ?: $entList->where("({$sql})");
            }

            $entList = $entList->all();

            $entList = obj2Arr($entList);

            $entList = control::array_flatten($entList);

            return $this->exportExcel($entList);
        }else
        {
            return $this->writeJson(200, $this->createPaging($page, $pageSize, $total), $list);
        }
    }

    //获取地址详情
    function selectDetail()
    {
        $orderId = $this->request()->getRequestParam('orderId') ?? '';

        $orderInfo = Order::create()->where('orderId', $orderId)->get();

        $entInfo = EntInfo::create()->where('orderId', $orderId)->get();

        $guDongInfo = EntGuDong::create()->where('orderId', $orderId)->all();

        $uploadFile = \App\HttpController\Models\Api\UploadFile::create()->where('orderId', $orderId)->all();

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
            'entInfo' => empty(obj2Arr($entInfo)) ? null : obj2Arr($entInfo),
            'guDongInfo' => empty(obj2Arr($guDongInfo)) ? null : obj2Arr($guDongInfo),
            'uploadFile' => empty(obj2Arr($uploadFile)) ? null : obj2Arr($uploadFile),
        ];

        return $this->writeJson(200, null, $info);
    }

    //办理完成但还未分配地址的
    function selectOrderIdByHandleStatus()
    {
        $orderId = Order::create()->where('handleStatus',4)->all();

        $orderId = obj2Arr($orderId);

        if (empty($orderId)) return $this->writeJson(200, null, []);

        $tmp = [];

        foreach ($orderId as $one)
        {
            $check = Addr::create()->where('orderId',$one['orderId'])->get();

            if (!empty($check)) continue;

            $tmp[] = EntInfo::create()->where('orderId',$one['orderId'])->get();
        }

        return $this->writeJson(200, null, array_filter($tmp));
    }
}
