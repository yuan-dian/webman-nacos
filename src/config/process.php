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
    'instance-registrar'    => [
        'handler' => InstanceRegistrarProcess::class,
        'count'   => 1
    ],
    'nacos-config-listener' => [
        'handler' => NacosConfigListener::class,
        'count'   => 1
    ],
];
