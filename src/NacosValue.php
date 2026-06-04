<?php

declare(strict_types=1);

namespace yuandian\WebmanNacos;

use yuandian\Container\Container;

/**
 * Utility class for directly reading Nacos config values.
 *
 * Usage: NacosValue::get('datasource.host', 'localhost')
 */
class NacosValue
{
    /**
     * Get a Nacos config value by dot-notation key.
     *
     * @param string $key Dot-notation config key, e.g. 'datasource.host'
     * @param mixed $default Default value if key not found
     * @param string $configId
     * @return mixed
     */
    public static function get(string $key, mixed $default = null, string $configId = 'default'): mixed
    {
        return NacosConfigBootstrap::getCachedConfig($key, $default, $configId);
    }

    /**
     * 通过配置类获取配置
     * @template T of object
     * @param class-string<T> $className
     * @return T
     * @date 2026/6/4 上午10:33
     * @author 原点 467490186@qq.com
     */
    public static function getConfigClass(string $className): object
    {
        return Container::getInstance()->make($className);
    }
}
