<?php

namespace App\HttpController\Service\UploadFile;

use App\HttpController\Models\Api\UploadFile;
use App\HttpController\Service\CommonService;
use App\HttpController\Service\ServiceBase;
use EasySwoole\Component\Singleton;
use EasySwoole\Mysqli\QueryBuilder;

class UploadFileService extends ServiceBase
{
    use Singleton;

    const STATUS_0 = 0;//已上传
    const STATUS_1 = 1;//等确认
    const STATUS_2 = 2;//等您确认
    const STATUS_3 = 3;//确认成功
    const STATUS_4 = 4;
    const STATUS_5 = 5;
    const STATUS_6 = 6;
    const STATUS_7 = 7;
    const STATUS_8 = 8;
    const STATUS_9 = 9;

    function uploadFile($filename, $orderId, $phone, $type, $status = 0 , $startTime = 0, $endTime = 0, $isc = 0)
    {
        //6-1
        if ($type === '6-1')
        {
            $info = UploadFile::create()->where('orderId', $orderId)->where('type', 6)->get();

            if (!empty($info) && (is_numeric($startTime) && is_numeric($endTime)))
            {
                $info->update([
                    'startTime' => $startTime === 0 ? 0 : substr($startTime,0,10),
                    'endTime' => $endTime === 0 ? 0 : substr($endTime,0,10),
                ]);
            }else
            {
                UploadFile::create()->data([
                    'orderId' => $orderId,
                    'phone' => $phone,
                    'type' => 6,
                    'startTime' => $startTime === 0 ? 0 : substr($startTime,0,10),
                    'endTime' => $endTime === 0 ? 0 : substr($endTime,0,10),
                ])->save();
            }

            return UploadFile::create()->where('orderId', $orderId)->get();
        }

        $filename = explode(',', trim($filename));

        $filename = array_filter($filename);

        $fileNum = count($filename);

        //如果不存在就插入
        $info = UploadFile::create()->where('orderId', $orderId)->where('type', $type)->get();

        if (empty($info)) {

            $insert = [
                'orderId' => $orderId,
                'phone' => $phone,
                'type' => $type,
                'fileNum' => $fileNum,
                'filename' => implode(',', $filename),
                'status' => $status,
                'startTime' => $startTime === 0 ? 0 : substr($startTime,0,10),
                'endTime' => $endTime === 0 ? 0 : substr($endTime,0,10),
            ];

            UploadFile::create()->data($insert)->save();

        } else {

            if ($isc === 1)
            {
                $oldFileName = $info->filename;

                $filename = $oldFileName.','.implode(',', $filename);

                $filename = trim($filename,',');

                //续写
                $info->update([
                    'fileNum' => $fileNum,
                    'filename' => $filename,
                    //'status' => QueryBuilder::inc(1),//自增1
                    'status' => $status,
                    'startTime' => $startTime === 0 ? 0 : substr($startTime,0,10),
                    'endTime' => $endTime === 0 ? 0 : substr($endTime,0,10),
                ]);

            }else
            {
                //如果存在就更新
                $info->update([
                    'fileNum' => $fileNum,
                    'filename' => implode(',', $filename),
                    //'status' => QueryBuilder::inc(1),//自增1
                    'status' => $status,
                    'startTime' => $startTime === 0 ? 0 : substr($startTime,0,10),
                    'endTime' => $endTime === 0 ? 0 : substr($endTime,0,10),
                ]);
            }
        }

        $res = UploadFile::create()->where('orderId', $orderId)->get();

        return $res;
    }


}
