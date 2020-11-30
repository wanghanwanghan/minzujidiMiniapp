<?php

namespace App\Crontab\Service;

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

        return true;
    }

    //风险监控
    private function runSupervisor()
    {
        return Crontab::getInstance()->addTask(RunSupervisor::class);
    }
}
