<?php

namespace App\WebSocket;

use EasySwoole\Socket\AbstractInterface\Controller;

class Index extends Controller
{
    function test()
    {
        // {"class":"Index","action":"test","content":{"uid":22357,"alianceNum":1}}

        $args = $this->caller()->getArgs();

        $fd = $this->caller()->getClient()->getFd();

        $this->response()->setMessage([
            'args' => $args,
            'fd' => $fd,
        ]);
    }


    //获取聊天内容，顺便打开websocket
    function getChatContent()
    {
        // {"class":"AssemblyHall","action":"getChatContent","content":{"uid":22357,"alianceNum":1}}

        $args = $this->caller()->getArgs();

        $uid = $args['uid'];

        $alianceNum = $args['alianceNum'];

        $fd = $this->caller()->getClient()->getFd();

        //加入内存表
        $this->createUidAndFdRelation($uid, $alianceNum, $fd);

        //取得最近的聊天记录
        $res = MysqlConnection::getInstance()->chatLimit($alianceNum, 200);

        $res = $this->fillData($res);

        $res = Sort::getInstance()->arraySort1($res, ['asc', 'unixTime']);

        $this->response()->setMessage($res);
    }

    //处理客户端发送过来的消息
    function getClientMsg()
    {
        // {"class":"AssemblyHall","action":"getClientMsg","content":{"uid":22357,"alianceNum":1,"content":"什么冬梅"}}

        $args = $this->caller()->getArgs();

        $uid = $args['uid'];

        $alianceNum = $args['alianceNum'];

        //用户输入内容
        $content = $args['content'];

        $client = $this->caller()->getClient();

        $res = TableManager::getInstance()->get(Aliance::ALIANCECHATS)->get($client->getFd());

        //重新建立关系
        if (!$res) $this->createUidAndFdRelation($uid, $alianceNum, $client->getFd());

        //异步mysql
        go(function () use ($uid, $alianceNum, $content) {
            MysqlConnection::getInstance()->insertOneChat($alianceNum, $uid, $content);
        });

        //异步推送
        TaskManager::getInstance()->async(function () use ($client, $content, $alianceNum) {
            $server = ServerManager::getInstance()->getSwooleServer();

            $fillDataObj = AssemblyHall::getInstance();

            $clientFd = $client->getFd();

            $res = TableManager::getInstance()->get(Aliance::ALIANCECHATS)->get($clientFd);

            $res['content'] = $content;
            $res['unixTime'] = time();

            $res = $fillDataObj->fillData([$res]);

            foreach ($server->connections as $fd) {
                if ($fd == $clientFd) continue;

                $fdInfo = TableManager::getInstance()->get(Aliance::ALIANCECHATS)->get($fd);

                if (!$fdInfo || $fdInfo['alianceNum'] != $alianceNum) continue;

                $server->push($fd, json_encode($res));
            }
        });
    }

    //建立uid和fd的关系
    function createUidAndFdRelation($uid, $alianceNum, $fd)
    {
        TableManager::getInstance()->get(Aliance::ALIANCECHATS)->set($fd, ['uid' => $uid, 'alianceNum' => $alianceNum]);

        return true;
    }

    //根据uid添加用户名和头像
    function fillData($data)
    {
        foreach ($data as &$one) {
            if (!isset($one['uid'])) continue;
            $one['name'] = trim(RedisClient::getInstance()->hget($one['uid'], 'name'));
            $one['avatar'] = trim(RedisClient::getInstance()->hget($one['uid'], 'avatar'));
            $one['date'] = FormatDate::getInstance()->formatDate($one['unixTime']);
        }
        unset($one);

        return $data;
    }
}