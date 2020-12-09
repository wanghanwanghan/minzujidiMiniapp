<?php

namespace App\HttpController\Business\Admin\PushMsg;

use App\HttpController\Business\BusinessBase;

class PushMsgController extends BusinessBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function revMsg()
    {
        return $this->writeJson();
    }





}
