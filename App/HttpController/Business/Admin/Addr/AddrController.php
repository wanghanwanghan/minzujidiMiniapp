<?php

namespace App\HttpController\Business\Admin\Addr;

use App\HttpController\Business\BusinessBase;
use App\HttpController\Models\Admin\Addr;
use App\HttpController\Models\Admin\AddrUse;
use App\HttpController\Service\CommonService;
use App\HttpController\Service\CreateTable;
use EasySwoole\Http\Message\UploadFile;
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
        $addrFile = $this->request()->getUploadedFile('addrFile');

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

        $content = jsonDecode($content);

        if (empty($content)) return $this->writeJson(201,null,null,'错误');

        $id = $content['id'];

        $content = control::removeArrKey($content,['id','created_at','updated_at']);

        Addr::create()->where('id',$id)->update($content);

        return $this->writeJson(200,null,null,'成功');
    }

    //获取地址列表
    function selectList()
    {
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 5;

        $list = Addr::create();
        $total = Addr::create();

        $list = $list->order('updated_at', 'desc')
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
