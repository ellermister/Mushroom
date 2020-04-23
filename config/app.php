<?php
return [
    // 服务监听配置
    'listen' => [
        'host' => '0.0.0.0',
        'port' => 9502,
    ],

    'memory' => [
        'table' => [
            'apnic'      => [
                'size'   => 102400,
                'column' => [
                    'value' => [\Swoole\Table::TYPE_STRING, 128]
                ]
            ],
            'gfw'        => [
                'size'   => 102400,
                'column' => [
                    'ip'              => [\Swoole\Table::TYPE_STRING, 64],
                    'fd'              => [\Swoole\Table::TYPE_INT, 64],
                    'china_status'    => [\Swoole\Table::TYPE_INT, 1],
                    'overseas_status' => [\Swoole\Table::TYPE_INT, 1],
                ]
            ],
            'gfw_node'   => [
                'size'   => 102400,
                'column' => [
                    'fd'   => [\Swoole\Table::TYPE_INT, 64],
                    'name' => [\Swoole\Table::TYPE_STRING, 64],
                    'auth' => [\Swoole\Table::TYPE_INT, 1],
                ]
            ],
            'gfw_client' => [
                'size'   => 102400,
                'column' => [
                    'fd'   => [\Swoole\Table::TYPE_INT, 64],
                    'auth' => [\Swoole\Table::TYPE_INT, 1],
                ]
            ]
        ]
    ],

    'command' => [
        'table' => [
            'imei' => [
                'size'   => 1024000,
                'column' => [
                    'text' => [\Swoole\Table::TYPE_STRING, 64],
                ]
            ]
        ]
    ],

    'test' => [
        'name' => 'joo'
    ]
];