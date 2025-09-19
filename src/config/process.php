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

use yuandian\WebmanNacos\Process\InstanceRegistrarProcess;
use yuandian\WebmanNacos\Process\NacosConfigListener;

return [
    'nacos-config-listener' => [
        'handler' => NacosConfigListener::class,
        'count'   => 1,
        // 开启协程需要设置为 Workerman\Events\Swoole::class 或者 Workerman\Events\Swow::class
        // 不开启协程使用定时器监听，配置更新可能有延迟
        'eventLoop' => ''
    ],
    'instance-registrar'    => [
        'handler' => InstanceRegistrarProcess::class,
        'count'   => 1
    ],
];
