<?php

namespace App\HttpController\Service;

use Vtiful\Kernel\Excel;
use wanghanwanghan\someUtils\control;

class ExportExcelService extends ServiceBase
{
    function __construct()
    {
        return parent::__construct();
    }

    //只填数据
    function export(Excel $fileObject,$entList): bool
    {
        $header = [
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
        ];

        $data = [];

        for ($i=0;$i<=60000;$i++)
        {
            $data[]=[
                control::getUuid(),
                control::getUuid(),
                control::getUuid(),
                control::getUuid(),
                control::getUuid(),
                control::getUuid(),
                control::getUuid(),
                control::getUuid(),
                control::getUuid(),
                control::getUuid(),
                control::getUuid(),
                control::getUuid(),
                control::getUuid(),
                control::getUuid(),
            ];
        }

        $fileObject->header($header)->data($data);

        return true;
    }






}
