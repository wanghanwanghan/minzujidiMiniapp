<?php

namespace App\HttpController\Business\Admin\Supervisor;

use App\HttpController\Business\BusinessBase;
use App\HttpController\Models\Admin\SupervisorEntNameInfo;
use App\HttpController\Models\Admin\SupervisorPhoneEntName;
use App\HttpController\Models\Api\Order;
use App\HttpController\Service\CommonService;
use Carbon\Carbon;

class SupervisorController extends BusinessBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    //获取列表
    function selectList()
    {
        $phone = 11111111111;
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $level = $this->request()->getRequestParam('level') ?? '';
        $type = $this->request()->getRequestParam('type') ?? '';
        $typeDetail = $this->request()->getRequestParam('typeDetail') ?? '';
        $timeRange = $this->request()->getRequestParam('timeRange') ?? '';
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        if (empty($entName))
        {
            //先确定是一个公司，还是全部公司
            $entList = SupervisorPhoneEntName::create()->where('phone', $phone)->where('status', 1)->all();

            $tmp = [];

            foreach ($entList as $one)
            {
                $tmp[] = $one->entName;
            }

            $entList = $tmp;

        }else
        {
            $entList = [$entName];
        }

        $detail = SupervisorEntNameInfo::create()->where('entName', $entList, 'IN');
        $resTotle = SupervisorEntNameInfo::create()->where('entName', $entList, 'IN');

        if (!empty($level))
        {
            if ($level === '高风险') $tmp = 1;
            if ($level === '风险') $tmp = 2;
            if ($level === '警示') $tmp = 3;
            if ($level === '提示') $tmp = 4;
            if ($level === '利好') $tmp = 5;

            $detail->where('level', $tmp);
            $resTotle->where('level', $tmp);
        }

        if (!empty($type))
        {
            if ($type === '司法风险') $tmp = 1;
            if ($type === '工商风险') $tmp = 2;
            if ($type === '管理风险') $tmp = 3;
            if ($type === '经营风险') $tmp = 4;

            $detail->where('type', $tmp);
            $resTotle->where('type', $tmp);
        }

        if (!empty($typeDetail))
        {
            if (in_array($typeDetail, ['失信被执行人', '工商变更', '严重违法', '经营异常'])) $tmp = 1;
            if (in_array($typeDetail, ['被执行人', '实际控制人变更', '行政处罚', '动产抵押'])) $tmp = 2;
            if (in_array($typeDetail, ['股权冻结', '最终受益人变更', '环保处罚', '土地抵押'])) $tmp = 3;
            if (in_array($typeDetail, ['裁判文书', '股东变更', '税收违法', '股权出质'])) $tmp = 4;
            if (in_array($typeDetail, ['开庭公告', '对外投资', '欠税公告', '股权质押'])) $tmp = 5;
            if (in_array($typeDetail, ['法院公告', '主要成员', '海关', '对外担保'])) $tmp = 6;
            if (in_array($typeDetail, ['查封冻结扣押', '一行两会'])) $tmp = 7;

            $detail->where('typeDetail', $tmp);
            $resTotle->where('typeDetail', $tmp);
        }

        if (!empty($timeRange))
        {
            is_numeric($timeRange) ?: $timeRange = 3;
            $date = Carbon::now()->subDays($timeRange)->timestamp;

            $detail->where('timeRange', $date, '>');
            $resTotle->where('timeRange', $date, '>');
        }

        $detail = $detail->order('created_at', 'desc')->limit($this->exprOffset($page, $pageSize), $pageSize)->all();

        $detail = obj2Arr($detail);

        $resTotle = $resTotle->count();

        $entList = SupervisorPhoneEntName::create()->where('phone', $phone)->where('status', 1)->all();

        $entList = obj2Arr($entList);

        return $this->writeJson(200, [
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $resTotle
        ], ['entList' => $entList, 'detail' => $detail], '查询成功');
    }

    //获取风险监控公司列表
    function selectEntList()
    {
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        //获取所有办理完成的公司
        $entList = Order::create()->where('status',3)->where('handleStatus',4)
            ->limit($this->exprOffset($page,$pageSize),$pageSize)
            ->all();

        !empty($entList) ? $entList = obj2Arr($entList) : $entList = [];

        foreach ($entList as $key => $oneEnt)
        {
            $entList[$key]['danger'] = SupervisorEntNameInfo::create()
                ->where(['entName' => $oneEnt['entName'],])
                ->where('level',[1,2],'in')->count();
        }

        CommonService::getInstance()->log4PHP($entList);

        $total = Order::create()->where('status',3)->where('handleStatus',4)->count();

        return $this->writeJson(200,$this->createPaging($page,$pageSize,$total),$entList);
    }







}
