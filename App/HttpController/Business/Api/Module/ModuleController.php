<?php

namespace App\HttpController\Business\Api\User;

use App\HttpController\Business\BusinessBase;
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

        $fee = (new ExprFee($userType,$taxType,$modifyAddr,$modifyArea,$proxy))->expr();

        return $this->writeJson(200,null,$fee,'成功');
    }



}
