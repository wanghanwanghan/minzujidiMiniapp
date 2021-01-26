<?php

namespace App\HttpController\Service;

use App\HttpController\Models\Api\Order;
use App\HttpController\Models\Api\UploadFile;
use EasySwoole\ORM\DbManager;
use EasySwoole\Pool\Manager;
use Vtiful\Kernel\Excel;
use wanghanwanghan\someUtils\control;

class ExportExcelService extends ServiceBase
{
    function __construct()
    {
        return parent::__construct();
    }

    //只填数据
    function export(Excel $fileObject, array $entList): bool
    {
        $entList = array_filter($entList);

        if (empty($entList)) return false;

        $entListTemp = '';

        foreach ($entList as $one)
        {
            $entList .= "'{$one}',";
        }

        $entListTemp = trim($entListTemp);
        $entListTemp = trim($entListTemp,',');

        $header = [
            '订单号',
            '档案编号',
            '企业名称',
            '提交人手机号',
            '提交人所属',
            '纳税类型',
            '是否变更地址',
            '是否跨区',
            '附加业务',
            '是否代理记账',
            '订单价格',
            '下单时间',
            '统一社会信用代码',
            '工商系统内企业状态',
            '工商系统内企业地址',
            '法人',
            '法人身份证',
            '法人手机号',
            '法人座机号',
            '法人联系地址',
            '法人电子邮箱',
            '经办人',
            '经办人身份证',
            '经办人手机号',
            '经办人座机号',
            '经办人联系地址',
            '经办人电子邮箱',
            '住所',
            '租赁合同开始时间',
            '租赁合同到期时间',
            '租赁合同是否到期',
            '管理协议开始时间',
            '管理协议到期时间',
            '管理协议是否到期',
            '申请单位',
            '管理费',
            '应收管理费',
        ];

        //不用orm方式了
        $obj = Manager::getInstance()->get('miniapp')->getObj();

        $sql = <<<SQL
SELECT
	orderTable.orderId,
	entInfoTable.fileNumber,
	orderTable.entName,
	orderTable.phone,
	orderTable.userType,
	orderTable.taxType,
	orderTable.modifyAddr,
	orderTable.modifyArea,
	orderTable.areaFeeItems,
	orderTable.proxy,
	orderTable.finalPrice,
	orderTable.created_at,
	entInfoTable.CODE,
	entInfoTable.entStatusInApi,
	entInfoTable.entAddrInApi,
	entInfoTable.fr,
	entInfoTable.frCode,
	entInfoTable.frPhone,
	entInfoTable.frTel,
	entInfoTable.frAddr,
	entInfoTable.frEmail,
	entInfoTable.jbr,
	entInfoTable.jbrCode,
	entInfoTable.jbrPhone,
	entInfoTable.jbrTel,
	entInfoTable.jbrAddr,
	entInfoTable.jbrEmail,
	entInfoTable.addr,
	uploadTable.startTime,
	uploadTable.endTime,
	"" AS zlhtIsExpire,
	"" AS xyStartTime,
	"" AS xyEndTime,
	"" AS xyIsExpire,
	entInfoTable.applyEnt,
	entInfoTable.managementPrice,
	entInfoTable.receivableManagementPrice 
FROM
	miniapp_order AS `orderTable`
	LEFT JOIN miniapp_ent_info AS entInfoTable ON orderTable.orderId = entInfoTable.orderId
	LEFT JOIN miniapp_upload_file AS uploadTable ON orderTable.orderId = uploadTable.orderId 
WHERE
	`orderTable`.`entName` IN ( {$entListTemp} ) 
	AND `orderTable`.`status` = 3 
	AND `orderTable`.`handleStatus` = 4 
	AND `uploadTable`.`type` = 4
SQL;

        $res = $obj->rawQuery($sql);

        Manager::getInstance()->get('miniapp')->recycleObj($obj);

        $list = obj2Arr($res);

        $tmp = [];

        if (!empty($list)) {
            foreach ($list as $one) {
                switch ($one['userType']) {
                    case 1:
                        $one['userType'] = '民族园内企业';
                        break;
                    case 2:
                        $one['userType'] = '新注册企业';
                        break;
                    case 3:
                        $one['userType'] = '渠道方';
                        break;
                }

                switch ($one['taxType']) {
                    case 1:
                        $one['taxType'] = '小规模纳税人';
                        break;
                    case 2:
                        $one['taxType'] = '一般纳税人';
                        break;
                }

                switch ($one['modifyAddr']) {
                    case 1:
                        $one['modifyAddr'] = '需要变更地址';
                        break;
                    case 2:
                        $one['modifyAddr'] = '不需要变更地址';
                        break;
                }

                switch ($one['modifyArea']) {
                    case 1:
                        $one['modifyArea'] = '不同区';
                        break;
                    case 2:
                        $one['modifyArea'] = '同区';
                        break;
                }

                $areaFeeItems = '';
                if (strpos($one['areaFeeItems'], '1') !== false) $areaFeeItems .= '工商,';
                if (strpos($one['areaFeeItems'], '2') !== false) $areaFeeItems .= '税务,';
                if (strpos($one['areaFeeItems'], '3') !== false) $areaFeeItems .= '银行,';
                if (strpos($one['areaFeeItems'], '4') !== false) $areaFeeItems .= '社保,';
                if (strpos($one['areaFeeItems'], '5') !== false) $areaFeeItems .= '公积金,';
                $one['areaFeeItems'] = trim($areaFeeItems, ',');

                switch ($one['proxy']) {
                    case 1:
                        $one['proxy'] = '需要代理记账';
                        break;
                    case 2:
                        $one['proxy'] = '不需要代理记账';
                        break;
                }

                $one['created_at'] = date('Y-m-d H:i:s', $one['created_at']);

                $one['startTime'] = date('Y-m-d', $one['startTime']);//upload type = 4 租赁合同时间
                if (!empty($one['endTime'])) {
                    $one['zlhtIsExpire'] = time() > $one['endTime'] ? '已经过期' : '未过期';
                }
                $one['endTime'] = date('Y-m-d', $one['endTime']);

                //添加管理协议时间，如果存在
                $xy = UploadFile::create()->where(['orderId' => $one['orderId'], 'type' => 6])->get();

                if (!empty($xy)) {
                    //
                    $one['xyStartTime'] = date('Y-m-d', $xy['startTime']);
                    $one['xyEndTime'] = date('Y-m-d', $xy['endTime']);
                    if (!empty($xy['endTime'])) {
                        $one['xyIsExpire'] = time() > $xy['endTime'] ? '已经过期' : '未过期';
                    }
                }

                $tmp[] = array_values($one);
            }
        }

        $fileObject->header($header)->data($tmp);

        return true;
    }


}
