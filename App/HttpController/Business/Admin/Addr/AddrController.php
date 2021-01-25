<?php

namespace App\HttpController\Business\Admin\Addr;

use App\HttpController\Business\BusinessBase;
use App\HttpController\Models\Admin\Addr;
use App\HttpController\Models\Admin\AddrUse;
use App\HttpController\Models\Api\EntInfo;
use App\HttpController\Models\Api\Order;
use App\HttpController\Service\CommonService;
use App\HttpController\Service\CreateTable;
use Carbon\Carbon;
use EasySwoole\Http\Message\UploadFile;
use EasySwoole\ORM\DbManager;
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
        $cond = explode(',',$cond);
        $export = $this->request()->getRequestParam('export') ?? '';
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 5;

        //
        $list = EntInfo::create()->alias('ent')->field([
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
        ])->join('miniapp_order as orderTable','ent.orderId = orderTable.orderId','left')
            ->join('miniapp_upload_file as uploadTable','ent.orderId = uploadTable.orderId','left')
            ->where('uploadTable.type',4);

//        if (!empty($keyword))
//        {
//            $list->where("ent.entName like '%{$keyword}%' or ent.addr like '%{$keyword}%' or ent.fr like '%{$keyword}%'");
//        }
//
//        if (!empty($cond))
//        {
//            $sql = '';
//
//            if (in_array('开业',$cond)) $sql .= 'ent.entStatusInApi like "%开业%" or ';
//            if (in_array('地址异常',$cond)) $sql .= 'ent.entStatusInApi like "%地址%" or ';
//            if (in_array('吊销',$cond)) $sql .= 'ent.entStatusInApi like "%吊销%" or ';
//            if (in_array('注销',$cond)) $sql .= 'ent.entStatusInApi like "%注销%" or ';
//            if (in_array('地址变更',$cond)) $sql .= 'ent.entStatusInApi not like "%民族园%" or ';
//            if (in_array('30天内到期',$cond)) $sql .= 'uploadTable.endTime < '.Carbon::now()->addDays(30)->timestamp;
//
//            $sql = trim($sql);
//            $sql = trim($sql,'or');
//
//            $list->where($sql);
//        }

        $list = $list->limit($this->exprOffset($page, $pageSize), $pageSize)->all();

        $list = obj2Arr($list);

        //
        $total = EntInfo::create()->alias('ent')->field([
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
            if (in_array('地址变更',$cond)) $sql .= 'ent.entStatusInApi not like "%民族园%" or ';
            if (in_array('30天内到期',$cond)) $sql .= 'uploadTable.endTime < '.Carbon::now()->addDays(30)->timestamp;

            $sql = trim($sql);
            $sql = trim($sql,'or');

            $total->where($sql);
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
                if (in_array('地址变更',$cond)) $sql .= 'ent.entStatusInApi not like "%民族园%" or ';
                if (in_array('30天内到期',$cond)) $sql .= 'uploadTable.endTime < '.Carbon::now()->addDays(30)->timestamp;

                $sql = trim($sql);
                $sql = trim($sql,'or');

                $entList->where($sql);
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
        $id = $this->request()->getRequestParam('id') ?? '';

        $info = Addr::create()->where('id',$id)->get();

        !empty($info) ?: $info = null;

        $detail = AddrUse::create()->where('addrId',$id)->order('updated_at', 'desc')->all();

        !empty($detail) ?: $detail = null;

        $res['info'] = $info;
        $res['detail'] = $detail;

        return $this->writeJson(200, null, $res);
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
