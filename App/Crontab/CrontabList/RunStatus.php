<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Models\Admin\SupervisorEntNameInfo;
use App\HttpController\Models\Admin\SupervisorPhoneEntName;
use App\HttpController\Models\Api\EntInfo;
use App\HttpController\Service\CommonService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use Carbon\Carbon;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use wanghanwanghan\someUtils\control;

class RunStatus extends AbstractCronTask
{
    private $crontabBase;

    private $headers = [
        'Authorization' => 'c8d03b8eb649ef9bcb08399ecec0c137e834a079a6cd36e2e8b91214b0961018',
    ];

    //每次执行任务都会执行构造函数
    function __construct()
    {
        $this->crontabBase = new CrontabBase();
    }

    static function getRule(): string
    {
        //一天一次
        return '0 15 */1 * *';
    }

    static function getTaskName(): string
    {
        return __CLASS__;
    }

    function run(int $taskId, int $workerIndex)
    {
        //$workerIndex是task进程编号
        //taskId是进程周期内第几个task任务
        //可以用task，也可以用process

        //取出本次要监控的企业列表，如果列表是空会跳到onException
        $target = SupervisorPhoneEntName::create()->where('status', 1)->all();

        $target = obj2Arr($target);

        if (empty($target)) throw new \Exception('target is null');

        foreach ($target as $one) {
            $this->addDetailInfo($one['entName']);
        }

        return true;
    }

    //补全公司信息，新进园区的企业，可能没有统一社会信用代码
    private function addDetailInfo($entName): void
    {
        $postData = [
            'phone' => 11111111111,
            'entName' => $entName,
        ];

        $url = 'https://api.meirixindong.com/api/v1/ts/getRegisterInfo';

        $res = (new CoHttpClient())->setDecode(true)->send($url, $postData, $this->headers);

        if ($res['code']==200 && !empty($res['result']))
        {
            $apiDetail = current($res['result']);

            $entStatus = $apiDetail['ENTSTATUS'] ?? '';
            $entCode = $apiDetail['SHXYDM'] ?? '';
            $entAddr = $apiDetail['DOM'] ?? '';

            $entInfo = EntInfo::create()->where('entName',$entName)->get();

            if (!empty($entInfo))
            {
                $entInfo->update([
                    'entStatusInApi' => $entStatus,
                    'code' => $entCode,
                    'entAddrInApi' => $entAddr,
                ]);
            }
        }
    }

    //全局异常
    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        $file = $throwable->getFile();
        $line = $throwable->getLine();
        $msg = $throwable->getMessage();

        CommonService::getInstance()->log4PHP(['file'=>$file,'line'=>$line,'msg'=>$msg]);
    }


}
