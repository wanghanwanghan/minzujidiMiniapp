<?php

namespace App\Task;

use App\HttpController\Service\CommonService;
use Carbon\Carbon;
use EasySwoole\Task\AbstractInterface\TaskInterface;

class Docx2pdf implements TaskInterface
{
    public $sendFile;

    function __construct($sendFile)
    {
        $this->sendFile = $sendFile;
    }

    function run(int $taskId, int $workerIndex)
    {
        $this->sendFile = array_filter($this->sendFile);

        foreach ($this->sendFile as $key => $oneFile) {
            if (strpos($oneFile, 'docx') === false) continue;
            $this->sendFile[$key] = str_replace('docx', 'pdf', $oneFile);
            file_get_contents('http://127.0.0.1:8992/docx2pdf/' . str_replace('/', '-', $oneFile));
        }

        return $this->sendFile;
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {

    }
}
