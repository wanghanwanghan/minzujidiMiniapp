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
            $this->CommRouterV1($routeCollector);
            $this->ModuleRouterV1($routeCollector);//模块
            $this->UserRouterV1($routeCollector);//用户
            $this->Notify($routeCollector);//通知
        });
    }

    private function CommRouterV1(RouteCollector $routeCollector)
    {
        $prefix='/Business/Api/Comm/CommController/';

        $routeCollector->addGroup('/comm',function (RouteCollector $routeCollector) use ($prefix)
        {
            $routeCollector->addRoute(['GET','POST'],'/uploadFile',$prefix.'uploadFile');//上传文件
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
            $routeCollector->addRoute(['GET','POST'],'/login',$prefix.'userLogin');//用户登录
            $routeCollector->addRoute(['GET','POST'],'/uploadFile',$prefix.'uploadFile');//上传文件
            $routeCollector->addRoute(['GET','POST'],'/addEntDetail',$prefix.'addEntDetail');//填写公司信息
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
            $routeCollector->addRoute(['GET','POST'],'/wx',$prefix.'wxNotify');//微信的通知

        });

        return true;
    }






}
