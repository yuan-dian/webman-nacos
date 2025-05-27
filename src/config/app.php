<?php
// +----------------------------------------------------------------------
// |
// +----------------------------------------------------------------------
// | @copyright (c) 原点 All rights reserved.
// +----------------------------------------------------------------------
// | Author: 原点 <467490186@qq.com>
// +----------------------------------------------------------------------
// | Date: 2025/5/26
// +----------------------------------------------------------------------

declare(strict_types=1);

return [
    'enable'                => true,
    /** 服务地址，多个服务使用,隔开 */
    'base_uri'              => 'http://127.0.0.1:8848',
    'username'              => null,
    'password'              => null,

    /** 需要配置监听的进程名称 */
    'listen_processes_name' => ['webman'],

    /** 需要扫描的配置类目录 */
    'scan_dirs' => [app_path() . '/config'],

    /** 长轮询等待时长 毫秒 */
    'long_pulling_timeout'  => 30000,

    /** 长轮询间隔 秒 */
    'long_pulling_interval' => 30,

    /** float 实例心跳间隔 秒 */
    'instance_heartbeat'    => 5.0,

    /**
     * 配置监听器
     */
    'config_listeners'      => [
        'default' => [
            /** Data ID */
            'dataId' => config('app.name', 'webman') . '.yaml',
            /** Group */
            'group'  => 'DEFAULT_GROUP',
            /** namespaceId */
            'tenant' => 'public',
            /** 配置类型 */
            'type'   => 'yaml',
        ],

        # 以下可以新增多个数组
    ],

    /**
     * 实例注册器
     */
    'instance_registrars'   => [
        'main' => [
            /** serviceName */
            config('app.name', 'webman'),

            /** ip */
            '127.0.0.1',

            /** port */
            8787,
            [
                'groupName'   => 'DEFAULT_GROUP',
                'namespaceId' => '',
                'enabled'     => 'true',
                'ephemeral'   => 'false'
            ],
        ],
        # 以下可以新增多个数组
    ]
];
