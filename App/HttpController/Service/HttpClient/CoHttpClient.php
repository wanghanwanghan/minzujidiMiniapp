<?php

namespace App\HttpController\Service\HttpClient;

use App\HttpController\Service\CommonService;
use EasySwoole\HttpClient\HttpClient;

class CoHttpClient
{
    private $decode = false;

    function setDecode($type)
    {
        $this->decode = $type;
        return $this;
    }

    function send($url = '', $postData = [], $headers = [], $options = [], $method = 'post')
    {
        $method = strtoupper($method);

        //新建请求
        $request = new HttpClient($url);

        //设置head头
        empty($headers) ?: $request->setHeaders($headers, true, false);

        try {
            //发送请求
            if ($method === 'POST') $data = $request->post($postData);
            if ($method === 'POSTJSON') $data = $request->postJson(json_encode($postData));
            if ($method === 'GET') $data = $request->get();

            //整理结果
            $data = $data->getBody();

        } catch (\Exception $e) {
            CommonService::getInstance()->log4PHP($e);
            return ['coHttpErr' => 'error'];
        }

        return $this->decode ? jsonDecode($data) : $data;
    }
}
