<?php

namespace App\HttpController\Service;

use Carbon\Carbon;
use EasySwoole\Component\Singleton;
use wanghanwanghan\someUtils\control;

class CommonService extends ServiceBase
{
    use Singleton;

    function __construct()
    {
        return parent::__construct();
    }

    //写log
    function log4PHP($content, $type = 'info', $filename = '')
    {
        (!is_array($content) && !is_object($content)) ?: $content = json_encode($content);

        return control::writeLog($content, LOG_PATH, $type, $filename);
    }
}
