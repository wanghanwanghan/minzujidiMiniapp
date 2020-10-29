<?php

namespace App\HttpController\Service;

use Carbon\Carbon;
use EasySwoole\Component\Singleton;

class OrderService extends ServiceBase
{
    use Singleton;

    function __construct()
    {
        return parent::__construct();
    }

    private function createOrderId($userType)
    {
        mt_srand();
        $ymd = Carbon::now()->format('YmdHis');
        return $ymd.$userType.mt_rand(0,9).'000000';
    }

    //创建订单
    function createOrder($userType,$taxType,$modifyAddr,$modifyArea,$proxy)
    {

    }


}
