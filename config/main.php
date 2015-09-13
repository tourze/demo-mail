<?php

return [

    'component' => [
        'http'    => [
            'class'  => 'tourze\Server\Component\Http',
            'params' => [
            ],
            'call'   => [
            ],
        ],
        'session' => [
            'class'  => 'tourze\Server\Component\Session',
            'params' => [
            ],
            'call'   => [
            ],
        ],
        'log'     => [
            'class'  => 'tourze\Server\Component\Log',
            'params' => [
            ],
            'call'   => [
            ],
        ],
    ],
    'server'    => [
        // Worker进程，接受用户提交请求
        'mail-worker' => [
            'socketName' => 'Text://0.0.0.0:42003',
            'initClass'  => '\mailServer\Bootstrap\MailWorker',
            'queueFile'  => ROOT_PATH . 'data/queue.php',
        ],
    ],

];
