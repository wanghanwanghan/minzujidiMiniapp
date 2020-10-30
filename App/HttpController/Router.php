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
            $this->CommonRouterV1($routeCollector);//公共
            $this->ModuleRouterV1($routeCollector);//模块
            $this->UserRouterV1($routeCollector);//用户
            // $this->Notify($routeCollector);//通知
        });
    }

    private function CommonRouterV1(RouteCollector $routeCollector)
    {
        $prefix='/Business/Api/Common/CommonController/';

        $routeCollector->addGroup('/comm',function (RouteCollector $routeCollector) use ($prefix)
        {

        });

        return true;
    }

    private function UserRouterV1(RouteCollector $routeCollector)
    {
        $prefix='/Business/Api/User/UserController/';

        $routeCollector->addGroup('/user',function (RouteCollector $routeCollector) use ($prefix)
        {
            $routeCollector->addRoute(['GET','POST'],'/create/order',$prefix.'createOrder');//创建订单
            $routeCollector->addRoute(['GET','POST'],'/reg',$prefix.'userReg');//用户注册
        });

        return true;
    }

    private function ModuleRouterV1(RouteCollector $routeCollector)
    {
        $prefix='/Business/Api/Module/ModuleController/';

        $routeCollector->addGroup('/module',function (RouteCollector $routeCollector) use ($prefix)
        {
            $routeCollector->addRoute(['GET','POST'],'/exprFee',$prefix.'exprFee');//计算费用
            $routeCollector->addRoute(['GET','POST'],'/vCodeSend',$prefix.'vCodeSend');//
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
