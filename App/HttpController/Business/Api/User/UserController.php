<?php

namespace App\HttpController\Business\Api\User;

use App\HttpController\Business\BusinessBase;
use App\HttpController\Service\CreateTable;
use wanghanwanghan\someUtils\control;

class UserController extends BusinessBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    //用户注册
    function reg()
    {

    }

    //用户登录
    function login()
    {

    }
}
