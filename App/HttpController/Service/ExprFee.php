<?php

namespace App\HttpController\Service;

class ExprFee extends ServiceBase
{
    private $userType;
    private $taxType;
    private $modifyAddr;
    private $modifyArea;
    private $areaFeeItems;
    private $proxy;

    function __construct($userType,$taxType,$modifyAddr,$modifyArea,$areaFeeItems,$proxy)
    {
        parent::__construct();

        $this->userType = (int)$userType;//用户类型
        $this->taxType = (int)$taxType;//小规模纳税人，一般纳税人
        $this->modifyAddr = (int)$modifyAddr;//变不变地址
        $this->modifyArea = (int)$modifyArea;//地址同不同区
        $this->areaFeeItems = array_filter(explode(',',$areaFeeItems));//不同区的话一堆需要转过来的
        $this->proxy = (int)$proxy;//是否需要代理记账
    }

    function expr()
    {
        $money = null;

        switch ($this->userType)
        {
            case 1:
                //会员企业
                $money = 0;
                if ($this->proxy === 1) $money += 2499;
                $money += count($this->areaFeeItems) * 600;
                break;
            case 2:
                //新企业
                $money = $this->newEnt();
                break;
            case 3:
                //渠道
                if ($this->taxType === 1) $money = 3999;
                if ($this->taxType === 2) $money = 5999;
                break;
        }

        return $money;
    }

    //新企业计算价格
    private function newEnt()
    {
        $money = 0;
        if ($this->taxType === 1) $money = 5999;
        if ($this->taxType === 2) $money = 7999;
        if ($this->proxy === 1) $money += 2499;
        if ($this->modifyAddr === 1 && $this->modifyArea === 1) $money += count($this->areaFeeItems) * 600;

        return $money;
    }










}
