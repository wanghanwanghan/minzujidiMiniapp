<?php

namespace App\Crontab\Service;

use App\Crontab\CrontabList\RunStatus;
use App\Crontab\CrontabList\RunSupervisor;
use EasySwoole\Component\Singleton;
use EasySwoole\EasySwoole\Crontab\Crontab;

class CrontabService
{
    use Singleton;

    //只能在mainServerCreate中调用
    function create()
    {
        $this->runSupervisor();
        $this->runEntStatus();

        return true;
    }

    //风险监控
    private function runSupervisor()
    {
        return Crontab::getInstance()->addTask(RunSupervisor::class);
    }

    //地址管理页
    private function runEntStatus()
    {
        return Crontab::getInstance()->addTask(RunStatus::class);
    }
}
