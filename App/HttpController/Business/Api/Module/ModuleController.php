<?php

namespace App\HttpController\Business\Api\Module;

use App\HttpController\Business\BusinessBase;
use App\HttpController\Models\Api\TradeType;
use App\HttpController\Service\CommonService;
use App\HttpController\Service\CreateTable;
use App\HttpController\Service\ExprFee;

class ModuleController extends BusinessBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    //计算费用
    function exprFee()
    {
        $userType = $this->request()->getRequestParam('userType');
        $taxType = $this->request()->getRequestParam('taxType');
        $modifyAddr = $this->request()->getRequestParam('modifyAddr');
        $modifyArea = $this->request()->getRequestParam('modifyArea');
        $areaFeeItems = $this->request()->getRequestParam('areaFeeItems');
        $proxy = $this->request()->getRequestParam('proxy');

        $fee = (new ExprFee($userType,$taxType,$modifyAddr,$modifyArea,$areaFeeItems,$proxy))->expr();

        return $this->writeJson(200,null,['fee'=>$fee],'成功');
    }

    //发送验证码
    function vCodeSend()
    {
        CreateTable::getInstance()->miniapp_ent_info();

        $phone = $this->request()->getRequestParam('phone');
        $type = $this->request()->getRequestParam('type');

        (int)$type === 1 ? $type = 'reg' : $type = 'login';

        CommonService::getInstance()->vCodeSend([$phone],$type);

        return $this->writeJson(200,null,null,'成功');
    }

    //经营范围
    function getTradeType()
    {
        $tradeType = $this->request()->getRequestParam('tradeType') ?? '';

        $tradeTypeInfo = TradeType::create()->where('id',$tradeType)->get();

        return $this->writeJson(200,null,$tradeTypeInfo,'成功');
    }


}
