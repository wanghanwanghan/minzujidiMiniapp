<?php

namespace App\HttpController\Business\Admin\Addr;

use App\HttpController\Business\BusinessBase;
use App\HttpController\Models\Admin\Addr;
use App\HttpController\Models\Admin\AddrUse;
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
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 5;

        is_numeric($id) ? $list = Addr::create()->where('addr.id',$id) : $list = Addr::create();
        is_numeric($id) ? $total = Addr::create()->where('id',$id) : $total = Addr::create();

        !is_numeric($isUse) ?: $list->where('addr.isUse',$isUse);
        !is_numeric($isUse) ?: $total->where('isUse',$isUse);

        $list = $list->alias('addr')
            ->field([
                'addr.id',
                'addr.name',
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
            ->join('miniapp_order as orderTable','addr.orderId = orderTable.orderId','left')
            ->order('addr.updated_at', 'desc')
            ->limit($this->exprOffset($page, $pageSize), $pageSize)
            ->all();

        $list = obj2Arr($list);

        $total = $total->count();

        return $this->writeJson(200, $this->createPaging($page, $pageSize, $total), $list);
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
}
