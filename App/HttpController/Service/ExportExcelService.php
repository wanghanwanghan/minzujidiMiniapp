<?php

namespace App\HttpController\Service;

use App\HttpController\Models\Api\Order;
use EasySwoole\ORM\DbManager;
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
        $entList = array_filter($entList);

        if (empty($entList)) return false;

        $header = [
            'orderTable.entName',
            'orderTable.phone',
            'orderTable.userType',
            'orderTable.taxType',
            'orderTable.modifyAddr',
            'orderTable.modifyArea',
            'orderTable.areaFeeItems',
            'orderTable.proxy',
            'orderTable.status',
            'orderTable.handleStatus',
            'orderTable.finalPrice',
            'orderTable.created_at',
            'entInfoTable.code',
            'entInfoTable.fr',
            'entInfoTable.frCode',
            'entInfoTable.frPhone',
            'entInfoTable.frTel',
            'entInfoTable.frAddr',
            'entInfoTable.frEmail',
            'entInfoTable.jbr',
            'entInfoTable.jbrCode',
            'entInfoTable.jbrPhone',
            'entInfoTable.jbrTel',
            'entInfoTable.jbrAddr',
            'entInfoTable.jbrEmail',
            'addrTable.category',
            'addrTable.number',
            'addrTable.name',
            'useAddrTable.startTime',
            'useAddrTable.endTime',
        ];

        $list = Order::create()->alias('orderTable')
            ->field([
                'orderTable.entName',
                'orderTable.phone',
                'orderTable.userType',
                'orderTable.taxType',
                'orderTable.modifyAddr',
                'orderTable.modifyArea',
                'orderTable.areaFeeItems',
                'orderTable.proxy',
                'orderTable.status',
                'orderTable.handleStatus',
                'orderTable.finalPrice',
                'orderTable.created_at',
                'entInfoTable.code',
                'entInfoTable.fr',
                'entInfoTable.frCode',
                'entInfoTable.frPhone',
                'entInfoTable.frTel',
                'entInfoTable.frAddr',
                'entInfoTable.frEmail',
                'entInfoTable.jbr',
                'entInfoTable.jbrCode',
                'entInfoTable.jbrPhone',
                'entInfoTable.jbrTel',
                'entInfoTable.jbrAddr',
                'entInfoTable.jbrEmail',
                'addrTable.category',
                'addrTable.number',
                'addrTable.name',
                'useAddrTable.startTime',
                'useAddrTable.endTime',
            ])
            ->join('miniapp_ent_info as entInfoTable','orderTable.orderId = entInfoTable.orderId','left')
            ->join('miniapp_addr as addrTable','orderTable.orderId = addrTable.orderId','left')
            ->join('miniapp_use_addr as useAddrTable','orderTable.orderId = useAddrTable.orderId','left')
            ->where('orderTable.entName',$entList,'in')
            ->where(['orderTable.status'=>3,'orderTable.handleStatus'=>4])
            ->all();

        $list = obj2Arr($list);

        $tmp = [];

        if (!empty($list))
        {
            foreach ($list as $one)
            {
                $tmp[] = array_values($one);
            }
        }

        $fileObject->header($header)->data($tmp);

        return true;
    }





}
