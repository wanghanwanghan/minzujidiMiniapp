<?php

namespace App\HttpController\Business\Admin\Addr;

use App\HttpController\Business\BusinessBase;
use App\HttpController\Models\Admin\Addr;
use App\HttpController\Models\Admin\AddrUse;
use App\HttpController\Models\Api\EntInfo;
use App\HttpController\Models\Api\Order;
use App\HttpController\Service\CommonService;
use App\HttpController\Service\CreateTable;
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
        $id = $this->request()->getRequestParam('id') ?? '';
        $isUse = $this->request()->getRequestParam('isUse') ?? '';
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $cond = $this->request()->getRequestParam('cond') ?? '';
        $export = $this->request()->getRequestParam('export') ?? '';
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 5;

        is_numeric($id) ? $list = Addr::create()->where('addr.id',$id) : $list = Addr::create();
        is_numeric($id) ? $entList = Addr::create()->where('addr.id',$id) : $entList = Addr::create();
        is_numeric($id) ? $total = Addr::create()->where('id',$id) : $total = Addr::create();

        !is_numeric($isUse) ?: $list->where('addr.isUse',$isUse);
        !is_numeric($isUse) ?: $entList->where('addr.isUse',$isUse);
        !is_numeric($isUse) ?: $total->where('isUse',$isUse);

        if (strpos($entName,',') !== false)
        {
            $entName = explode(',',$entName);
            $entName = array_filter($entName);
        }else
        {
            empty($entName) ?: $list->where('ent.entName',"%{$entName}%",'like');
            empty($entName) ?: $total = EntInfo::create()->where('entName',"%{$entName}%",'like');
        }

        $list->alias('addr')
            ->field([
                'addr.id',
                'addr.name',
                'addr.category',
                'ent.code',
                'ent.entName',
                'ent.regEntName',
                'addr.isUse',
                'addrUse.startTime',
                'addrUse.endTime',
                'ent.fr',
                'ent.frPhone',
                'ent.jbr',
                'ent.jbrPhone',
                'orderTable.finalPrice',
            ])
            ->join('miniapp_use_addr as addrUse','addr.orderId = addrUse.orderId','left')
            ->join('miniapp_ent_info as ent','addr.orderId = ent.orderId','left')
            ->join('miniapp_order as orderTable','addr.orderId = orderTable.orderId','left');

        //查询条件
        switch ((int)$cond)
        {
            case 1:
                //1是按照地址过期时间从近到远
                $list->order('addrUse.endTime', 'asc');
                break;
            default:
        }

        $list = $list->limit($this->exprOffset($page, $pageSize), $pageSize)->all();

        $list = obj2Arr($list);

        $total = $total->count();

        if ((int)$export === 1)
        {
            if (!empty($entName) && is_array($entName))
            {
                $entList = $entName;
            }else
            {
                $entList = $entList->alias('addr')
                    ->field(['ent.entName'])
                    ->join('miniapp_use_addr as addrUse','addr.orderId = addrUse.orderId','left')
                    ->join('miniapp_ent_info as ent','addr.orderId = ent.orderId','left')
                    ->join('miniapp_order as orderTable','addr.orderId = orderTable.orderId','left')
                    ->all();

                $entList = obj2Arr($entList);

                !empty($entList) ?: $entList = [];

                $entList = control::array_flatten($entList);
            }

            CommonService::getInstance()->log4PHP($entList);

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
