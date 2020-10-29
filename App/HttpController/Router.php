<?php

namespace App\HttpController;

use EasySwoole\Http\AbstractInterface\AbstractRouter;
use FastRoute\RouteCollector;

class Router extends AbstractRouter
{
    public function initialize(RouteCollector $routeCollector)
    {
        //全局模式拦截下,路由将只匹配Router.php中的控制器方法响应,将不会执行框架的默认解析
        $this->setGlobalMode(true);

        $routeCollector->addGroup('/api/v1',function (RouteCollector $routeCollector)
        {
            $this->CommonRouterV1($routeCollector);//公共功能
            // $this->Notify($routeCollector);//通知
        });
    }

    private function CommonRouterV1(RouteCollector $routeCollector)
    {
        $prefix='/Business/Api/Common/CommonController/';

        $routeCollector->addGroup('/comm',function (RouteCollector $routeCollector) use ($prefix)
        {
            $routeCollector->addRoute(['GET','POST'],'/image/upload',$prefix.'imageUpload');//图片上传
            $routeCollector->addRoute(['GET','POST'],'/create/image/verifyCode',$prefix.'imageVerifyCode');//创建图片验证码
            $routeCollector->addRoute(['GET','POST'],'/create/sms/verifyCode',$prefix.'smsVerifyCode');//发送手机验证码
            $routeCollector->addRoute(['GET','POST'],'/userLngLatUpload',$prefix.'userLngLatUpload');//上传用户经纬度
        });

        return true;
    }

    private function Notify(RouteCollector $routeCollector)
    {
        $prefix='/Business/Api/Notify/NotifyController/';

        $routeCollector->addGroup('/notify',function (RouteCollector $routeCollector) use ($prefix)
        {
            $routeCollector->addRoute(['GET','POST'],'/wx',$prefix.'wxNotify');//微信的通知 信动
            $routeCollector->addRoute(['GET','POST'],'/wx_wh',$prefix.'wxNotify_wh');//微信的通知 伟衡
        });

        return true;
    }






}
