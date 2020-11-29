<?php

namespace App\HttpController\Business\Admin\Supervisor;

use App\HttpController\Business\BusinessBase;

class SupervisorController extends BusinessBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    //获取列表
    function selectList()
    {

    }

}
