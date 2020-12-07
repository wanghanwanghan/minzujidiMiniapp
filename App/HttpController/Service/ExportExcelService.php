<?php

namespace App\HttpController\Service;

use Vtiful\Kernel\Excel;

class ExportExcelService extends ServiceBase
{
    function __construct()
    {
        return parent::__construct();
    }

    //只填数据
    function export(Excel $fileObject): bool
    {
        $fileObject->header(['name', 'age'])
            ->data([['viest', 21]]);

        return true;
    }






}
