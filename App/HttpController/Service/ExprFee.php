<?php

namespace App\HttpController\Service;

class ExprFee extends ServiceBase
{
    private $userType;
    private $taxType;
    private $modifyAddr;
    private $modifyArea;
    private $proxy;

    function __construct($userType,$taxType,$modifyAddr,$modifyArea,$proxy)
    {
        $this->userType = (int)$userType;
        $this->taxType = (int)$taxType;
        $this->modifyAddr = (int)$modifyAddr;
        $this->modifyArea = (int)$modifyArea;
        $this->proxy = (int)$proxy;
        return parent::__construct();
    }

    function expr()
    {
        $money = null;

        switch ($this->userType)
        {
            case 1:
                //会员企业
                $money = 0;
                break;
            case 2:
                //新企业
                $money = $this->newEnt();
                break;
            case 3:
                //渠道
                if ($this->taxType === 1) $money = 4000;
                if ($this->taxType === 2) $money = 6000;
                break;
        }

        return $money;
    }

    //新企业计算价格
    private function newEnt()
    {
        if ($this->taxType === 1) $money = 6000;
        if ($this->taxType === 2) $money = 8000;
        if ($this->proxy === 1) $money += 2500;
        if ($this->modifyArea === 1) $money += 4000;

        return $money;
    }










}
