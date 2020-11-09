<?php

namespace App\HttpController\Service\Pay\wx;

use App\HttpController\Models\Api\UploadFile;
use App\HttpController\Service\ServiceBase;
use EasySwoole\Component\Singleton;
use EasySwoole\Mysqli\QueryBuilder;

class UploadFileService extends ServiceBase
{
    use Singleton;

    const STATUS_1 = 1;
    const STATUS_2 = 2;
    const STATUS_3 = 3;
    const STATUS_4 = 4;
    const STATUS_5 = 5;
    const STATUS_6 = 6;
    const STATUS_7 = 7;
    const STATUS_8 = 8;
    const STATUS_9 = 9;

    function uploadFile($filename, $orderId, $phone, $type)
    {
        $filename = explode(',', trim($filename));

        $filename = array_filter($filename);

        $fileNum = count($filename);

        //如果不存在就插入
        $info = UploadFile::create()->where('orderId',$orderId)->get();

        if (empty($info))
        {
            $insert = [
                'orderId' => $orderId,
                'phone' => $phone,
                'type' => $type,
                'fileNum' => $fileNum,
                'filename' => implode(',', $filename)
            ];

            UploadFile::create()->data($insert)->save();

            $res = UploadFile::create()->where('orderId',$orderId)->get();

        }else
        {
            //如果存在就更新
            $info->update([
                'fileNum' => $fileNum,
                'filename' => implode(',', $filename),
                //'status' => QueryBuilder::inc(1),//自增1
            ]);

            $res = UploadFile::create()->where('orderId',$orderId)->get();
        }

        return $res;
    }


}
