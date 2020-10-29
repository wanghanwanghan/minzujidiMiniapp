<?php

namespace App\HttpController\Business\Api\User;

use App\HttpController\Business\BusinessBase;
use App\HttpController\Service\CreateTable;

class UserController extends BusinessBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    //用户注册
    function reg()
    {
        CreateTable::getInstance()->miniapp_ent_type();
        var_dump(123123);
    }

    //用户登录
    function login()
    {

    }
}
