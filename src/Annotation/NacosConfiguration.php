<?php
// +----------------------------------------------------------------------
// | 
// +----------------------------------------------------------------------
// | @copyright (c) 原点 All rights reserved.
// +----------------------------------------------------------------------
// | Author: 原点 <467490186@qq.com>
// +----------------------------------------------------------------------
// | Date: 2025/5/13
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace yuandian\WebmanNacos\Annotation;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class NacosConfiguration
{
    public function __construct(public string $prefix, public string $configId = 'default')
    {
    }
}