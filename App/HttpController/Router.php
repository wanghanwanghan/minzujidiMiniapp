<?php

namespace App\HttpController;

use EasySwoole\Http\AbstractInterface\AbstractRouter;
use FastRoute\RouteCollector;

class Router extends AbstractRouter
{
    function initialize(RouteCollector $routeCollector)
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

        $routeCollector->addGroup('/admin/v1',function (RouteCollector $routeCollector)
        {
            $this->Admin($routeCollector);
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
            $routeCollector->addRoute(['GET','POST'],'/pay/order',$prefix.'payOrder');//支付订单
            $routeCollector->addRoute(['GET','POST'],'/get/order/list',$prefix.'getOrderList');//获取订单列表
            $routeCollector->addRoute(['GET','POST'],'/reg',$prefix.'userReg');//用户注册
            $routeCollector->addRoute(['GET','POST'],'/login',$prefix.'userLogin');//用户登录
            $routeCollector->addRoute(['GET','POST'],'/editPassword',$prefix.'editPassword');//修改密码
            $routeCollector->addRoute(['GET','POST'],'/uploadFile',$prefix.'uploadFile');//上传文件
            $routeCollector->addRoute(['GET','POST'],'/uploadFile/list',$prefix.'uploadFileList');//获取上传文件列表
            $routeCollector->addRoute(['GET','POST'],'/addEntInfo',$prefix.'addEntInfo');//填写公司信息
            $routeCollector->addRoute(['GET','POST'],'/addEntGuDong',$prefix.'addEntGuDong');//填写股东信息
            $routeCollector->addRoute(['GET','POST'],'/downloadFile',$prefix.'downloadFile');//下载文件
            $routeCollector->addRoute(['GET','POST'],'/entStatus',$prefix.'entStatus');//办理状态
            $routeCollector->addRoute(['GET','POST'],'/uploadFilePage',$prefix.'uploadFilePage');//上传文件页面
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
            $routeCollector->addRoute(['GET','POST'],'/getTradeType',$prefix.'getTradeType');//
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

    private function Admin(RouteCollector $routeCollector)
    {
        //订单
        $routeCollector->addGroup('/order',function (RouteCollector $routeCollector)
        {
            $prefix='/Business/Admin/Order/OrderController/';
            $routeCollector->addRoute(['GET','POST'],'/refundOrder',$prefix.'refundOrder');
            $routeCollector->addRoute(['GET','POST'],'/deleteOrder',$prefix.'deleteOrder');
            $routeCollector->addRoute(['GET','POST'],'/selectList',$prefix.'selectList');
            $routeCollector->addRoute(['GET','POST'],'/selectDetail',$prefix.'selectDetail');
            $routeCollector->addRoute(['GET','POST'],'/updateDetail',$prefix.'updateDetail');
            $routeCollector->addRoute(['GET','POST'],'/createSpecial',$prefix.'createSpecial');
            $routeCollector->addRoute(['GET','POST'],'/editHandleStatus',$prefix.'editHandleStatus');
            $routeCollector->addRoute(['GET','POST'],'/editOrderAddr',$prefix.'editOrderAddr');
            $routeCollector->addRoute(['GET','POST'],'/adminUploadFile',$prefix.'adminUploadFile');
            $routeCollector->addRoute(['GET','POST'],'/addEntNameToTable',$prefix.'addEntNameToTable');
        });

        //地址
        $routeCollector->addGroup('/addr',function (RouteCollector $routeCollector)
        {
            $prefix='/Business/Admin/Addr/AddrController/';
            $routeCollector->addRoute(['GET','POST'],'/insertAddr',$prefix.'insertAddr');
            $routeCollector->addRoute(['GET','POST'],'/editAddr',$prefix.'editAddr');
            $routeCollector->addRoute(['GET','POST'],'/selectList',$prefix.'selectList');
            $routeCollector->addRoute(['GET','POST'],'/selectDetail',$prefix.'selectDetail');
            $routeCollector->addRoute(['GET','POST'],'/selectOrderIdByHandleStatus',$prefix.'selectOrderIdByHandleStatus');
        });

        //监控
        $routeCollector->addGroup('/supervisor',function (RouteCollector $routeCollector)
        {
            $prefix='/Business/Admin/Supervisor/SupervisorController/';
            $routeCollector->addRoute(['GET','POST'],'/selectList',$prefix.'selectList');
            $routeCollector->addRoute(['GET','POST'],'/selectEntList',$prefix.'selectEntList');
        });

        //消息推送
        $routeCollector->addGroup('/pushMsg',function (RouteCollector $routeCollector)
        {
            $prefix='/Business/Admin/PushMsg/PushMsgController/';
            $routeCollector->addRoute(['GET','POST'],'/revMsg',$prefix.'revMsg');
        });
    }




}
