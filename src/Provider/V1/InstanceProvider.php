<?php
// +----------------------------------------------------------------------
// | 
// +----------------------------------------------------------------------
// | @copyright (c) 原点 All rights reserved.
// +----------------------------------------------------------------------
// | Author: 原点 <467490186@qq.com>
// +----------------------------------------------------------------------
// | Date: 2025/9/18
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace yuandian\WebmanNacos\Provider\V1;

use JetBrains\PhpStorm\ArrayShape;
use Workerman\Http\Response;
use yuandian\WebmanNacos\AbstractProvider;

class InstanceProvider extends AbstractProvider
{
    public function register(
        string $ip,
        int $port,
        string $serviceName,
        #[ArrayShape([
            'groupName'   => '',
            'clusterName' => '',
            'namespaceId' => '',
            'weight'      => 99.0,
            'metadata'    => '',
            'enabled'     => true,
            'ephemeral'   => false, // 是否临时实例
        ])]
        array $optional = []
    ): Response {
        return $this->request('POST', 'nacos/v1/ns/instance', [
            'query' => $this->filter(array_merge($optional, [
                'serviceName' => $serviceName,
                'ip'          => $ip,
                'port'        => $port,
            ])),
        ]);
    }

    public function registerAsync(
        string $ip,
        int $port,
        string $serviceName,
        #[ArrayShape([
            'groupName'   => '',
            'clusterName' => '',
            'namespaceId' => '',
            'weight'      => 99.0,
            'metadata'    => '',
            'enabled'     => true,
            'ephemeral'   => false, // 是否临时实例
        ])]
        array $optional = [],
        ?callable $success = null,
        ?callable $error = null,
    ): void {
        $this->requestAsync('POST', 'nacos/v1/ns/instance', [
            'query'   => $this->filter(array_merge($optional, [
                'serviceName' => $serviceName,
                'ip'          => $ip,
                'port'        => $port,
            ])),
            'success' => $success,
            'error'   => $error,
        ]);
    }

    public function delete(
        string $serviceName,
        string $groupName,
        string $ip,
        int $port,
        #[ArrayShape([
            'clusterName' => '',
            'namespaceId' => '',
            'ephemeral'   => false,
        ])]
        array $optional = []
    ): Response {
        return $this->request('DELETE', 'nacos/v1/ns/instance', [
            'query' => $this->filter(array_merge($optional, [
                'serviceName' => $serviceName,
                'groupName'   => $groupName,
                'ip'          => $ip,
                'port'        => $port,
            ])),
        ]);
    }

    public function update(
        string $ip,
        int $port,
        string $serviceName,
        #[ArrayShape([
            'groupName'   => '',
            'clusterName' => '',
            'namespaceId' => '',
            'weight'      => 0.99,
            'metadata'    => '', // json
            'enabled'     => false,
            'ephemeral'   => false,
        ])]
        array $optional = []
    ): Response {
        return $this->request('PUT', 'nacos/v1/ns/instance', [
            'query' => $this->filter(array_merge($optional, [
                'serviceName' => $serviceName,
                'ip'          => $ip,
                'port'        => $port,
            ])),
        ]);
    }

    public function list(
        string $serviceName,
        #[ArrayShape([
            'groupName'   => '',
            'namespaceId' => '',
            'clusters'    => '', // 集群名称
            'healthyOnly' => false,
        ])]
        array $optional = []
    ): Response {
        return $this->request('GET', 'nacos/v1/ns/instance/list', [
            'query' => $this->filter(array_merge($optional, [
                'serviceName' => $serviceName,
            ])),
        ]);
    }

    public function detail(
        string $ip,
        int $port,
        string $serviceName,
        #[ArrayShape([
            'groupName'   => '',
            'namespaceId' => '',
            'cluster'     => '',
            'healthyOnly' => false,
            'ephemeral'   => false,
        ])]
        array $optional = []
    ): Response {
        return $this->request('GET', 'nacos/v1/ns/instance', [
            'query' => $this->filter(array_merge($optional, [
                'ip'          => $ip,
                'port'        => $port,
                'serviceName' => $serviceName,
            ])),
        ]);
    }

    public function beat(
        string $serviceName,
        #[ArrayShape([
            'ip'          => '',
            'port'        => 9501,
            'serviceName' => '',
            'cluster'     => '',
            'weight'      => 1,
        ])]
        array $beat = [],
        ?string $groupName = null,
        ?string $namespaceId = null,
        ?bool $ephemeral = null,
        bool $lightBeatEnabled = false
    ): Response {
        return $this->request('PUT', 'nacos/v1/ns/instance/beat', [
            'query' => $this->filter([
                'serviceName' => $serviceName,
                'ip'          => $beat['ip'] ?? null,
                'port'        => $beat['port'] ?? null,
                'groupName'   => $groupName,
                'namespaceId' => $namespaceId,
                'ephemeral'   => $ephemeral,
                'beat'        => !$lightBeatEnabled ? json_encode($beat) : '',
            ]),
        ]);
    }

    public function updateHealth(
        string $ip,
        int $port,
        string $serviceName,
        bool $healthy,
        #[ArrayShape([
            'namespaceId' => '',
            'groupName'   => '',
            'clusterName' => '',
        ])]
        array $optional = []
    ): Response {
        return $this->request('PUT', 'nacos/v1/ns/health/instance', [
            'query' => $this->filter(array_merge($optional, [
                'ip'          => $ip,
                'port'        => $port,
                'serviceName' => $serviceName,
                'healthy'     => $healthy,
            ])),
        ]);
    }

}