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
        $proxy = $this->request()->getRequestParam('proxy');
        $tradeType = $this->request()->getRequestParam('tradeType');

        $fee = (new ExprFee($userType,$taxType,$modifyAddr,$modifyArea,$proxy))->expr();

        try
        {
            $tradeType = TradeType::create()->where('id',$tradeType)->get();

        }catch (\Throwable $e)
        {
            CommonService::getInstance()->log4PHP($e->getMessage());
            $tradeType=null;
        }

        return $this->writeJson(200,null,['fee'=>$fee,'tradeType'=>$tradeType],'成功');
    }

    //发送验证码
    function vCodeSend()
    {
        $phone = $this->request()->getRequestParam('phone');
        $type = $this->request()->getRequestParam('type');

        (int)$type === 1 ? $type = 'reg' : $type = 'login';

        CommonService::getInstance()->vCodeSend($phone,$type);

        return $this->writeJson(200,null,null,'成功');
    }



}
