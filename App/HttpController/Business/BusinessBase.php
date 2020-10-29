<?php

namespace App\HttpController\Business;

use App\HttpController\Index;

class BusinessBase extends Index
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }
}
