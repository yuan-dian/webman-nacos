<?php

declare(strict_types=1);

namespace yuandian\WebmanNacos;

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
}
