<?php

namespace App\HttpController\Business\Api\Comm;

use App\HttpController\Business\BusinessBase;
use EasySwoole\Http\Message\UploadFile;
use wanghanwanghan\someUtils\control;

class CommController extends BusinessBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function createFilename()
    {
        return control::getUuid();
    }

    //上传文件
    function uploadFile()
    {
        $fileArr = $this->request()->getUploadedFiles();

        if (empty($fileArr)) return $this->writeJson(201, null, null, '未发现上传文件');

        $tmp = [];

        foreach ($fileArr as $key => $file)
        {
            if ($file instanceof UploadFile)
            {
                //提取文件后缀
                $ext = explode('.', $file->getClientFilename());
                $ext = end($ext);

                //新建文件名
                $filename = $this->createFilename() . '.' . $ext;

                //移动到文件夹
                $file->moveTo(FILE_PATH . $filename);

                $tmp[]=$filename;
            }
        }

        return $this->writeJson(200,null,$tmp,'上传成功');
    }


}
