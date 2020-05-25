<?php

return [
    /**
     * DB数据库服务器集群
     */
    'servers' => array(
        'db_master' => array(                     //服务器标记
            'type'     => 'mysql',                //数据库类型，暂时只支持：mysql
            'host'     => env('DB_HOST'),            //数据库域名
            'database' => env('DB_DATABASE'),               //数据库名字
            'user'     => env('DB_USERNAME'),                 //数据库用户名
            'password' => env('DB_PASSWORD'),         //数据库密码
            'port'     => env('DB_PORT', 3306),                   //数据库端口
            'charset'  => 'UTF8',                 //数据库字符集
        ),
    ),

    /**
     * 自定义路由表
     */
    'tables'  => array(
        // 通用路由
        '__default__' => array(                     // 固定的系统标志，不能修改！
            'prefix' => '',                         // 数据库统一表名前缀，无前缀保留空
            'key'    => 'id',                       // 数据库统一表主键名，通常为id
            'map'    => array(                      // 数据库统一默认存储路由
                array('db' => 'db_master'),         // db_master对应前面servers.db_master配置，须对应！
            ),
        ),

    ),
];