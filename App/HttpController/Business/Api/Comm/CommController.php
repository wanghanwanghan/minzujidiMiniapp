<?php

namespace App\HttpController\Business\Api\Comm;

use App\HttpController\Business\BusinessBase;
use EasySwoole\Http\Message\UploadFile;

class CommController extends BusinessBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    //上传文件
    function uploadFile()
    {
        $fileArr = $this->request()->getUploadedFiles();

        if (empty($fileArr)) return $this->writeJson(201,null,null,'未发现上传文件');

        foreach ($fileArr as $key => $file)
        {
            if ($file instanceof UploadFile)
            {
                var_dump($file->getClientFilename());
            }
        }











    }










}
