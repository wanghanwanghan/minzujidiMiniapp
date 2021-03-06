<?php
return [
    'SERVER_NAME' => "minzujidiMiniapp",
    'MAIN_SERVER' => [
        'LISTEN_ADDRESS' => '0.0.0.0',
        'PORT' => 8991,
        'SERVER_TYPE' => EASYSWOOLE_WEB_SERVER, //可选为 EASYSWOOLE_SERVER  EASYSWOOLE_WEB_SERVER EASYSWOOLE_WEB_SOCKET_SERVER,EASYSWOOLE_REDIS_SERVER
        'SOCK_TYPE' => SWOOLE_TCP,
        'RUN_MODEL' => SWOOLE_PROCESS,
        'SETTING' => [
            'worker_num' => 4,
            'reload_async' => true,
            'max_wait_time' => 3,
            'dispatch_mode' => 2,
            'package_max_length' => 30 * 1024 * 1024,
        ],
        'TASK' => [
            'workerNum' => 2,
            'maxRunningNum' => 128,
            'timeout' => 15
        ]
    ],
    'TEMP_DIR' => '/tmp',
    'LOG_DIR' => '/tmp'
];
