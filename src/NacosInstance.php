<?php
// +----------------------------------------------------------------------
// |
// +----------------------------------------------------------------------
// | @copyright (c) 原点 All rights reserved.
// +----------------------------------------------------------------------
// | Author: 原点 <467490186@qq.com>
// +----------------------------------------------------------------------
// | Date: 2026/6/2
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace yuandian\WebmanNacos;

use yuandian\Container\Container;

/**
 * 用于查询 Nacos 命名服务实例列表的工具类(需要在协程环境中调用)
 * Usage:
 *   $nodes = NacosInstance::list('my-service');
 *   $node  = NacosInstance::getOne('my-service');
 */
class NacosInstance
{
    /**
     * Resolve missing optional params from plugin config.
     * When groupName or namespaceId not specified, infer from first config_listener entry.
     */
    private static function resolveOptional(array $optional): array
    {
        if (!isset($optional['groupName']) || !isset($optional['namespaceId'])) {
            $listeners = \Webman\Config::get('plugin.yuandian.webman-nacos.app.instance_registrars', []);
            $option = $listeners['default'][3] ?? [];
            $optional['groupName'] ??= $option['group'] ?? 'DEFAULT_GROUP';
            $optional['namespaceId'] ??= $option['namespaceId'] ?? 'public';
        }
        return $optional;
    }

    /**
     * Get all valid (healthy) instances for a service.
     *
     * @param string $serviceName Service name to query
     * @param array $optional Optional params: groupName, namespaceId, clusters, healthyOnly
     * @return array List of node entries (each contains ip, port, clusterName, etc.)
     */
    public static function list(string $serviceName, array $optional = []): array
    {
        $optional = self::resolveOptional($optional);
        $client = Container::getInstance()->make(NacosClient::class);
        return $client->getValidNodes($serviceName, $optional);
    }

    /**
     * Get a random healthy instance for a service.
     *
     * @param string $serviceName Service name to query
     * @param array $optional Optional params: groupName, namespaceId, clusters, healthyOnly
     * @return array|null Random node entry, or null if no healthy instances found
     */
    public static function getOne(string $serviceName, array $optional = []): ?array
    {
        $nodes = self::list($serviceName, $optional);
        if (empty($nodes)) {
            return null;
        }
        return $nodes[array_rand($nodes)];
    }
}
