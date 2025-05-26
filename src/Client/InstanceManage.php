<?php
// +----------------------------------------------------------------------
// | 
// +----------------------------------------------------------------------
// | @copyright (c) 原点 All rights reserved.
// +----------------------------------------------------------------------
// | Author: 原点 <467490186@qq.com>
// +----------------------------------------------------------------------
// | Date: 2025/5/21
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace yuandian\WebmanNacos\Client;

use GuzzleHttp\RequestOptions;

/**
 * 实例管理
 */
class InstanceManage
{
    public function __construct(private NacosClient $client)
    {

    }

    /**
     * 注册服务
     * @param string $ip
     * @param int $port
     * @param string $serviceName
     * @param array $optional
     * @return \GuzzleHttp\Promise\PromiseInterface
     * @date 2025/5/26 上午11:57
     * @author 原点 467490186@qq.com
     */
    public function registerAsync(string $ip, int $port, string $serviceName, array $optional = []){

        $options = [
            RequestOptions::QUERY => $this->filter(array_merge($optional, [
                'serviceName' => $serviceName,
                'ip' => $ip,
                'port' => $port,
            ])),
        ];
        return $this->client->requestAsync('POST', '/nacos/v1/ns/instance', $options);
    }

    /**
     * 删除配置
     * @param string $serviceName
     * @param string $groupName
     * @param string $ip
     * @param int $port
     * @param array $optional
     * @return \GuzzleHttp\Promise\PromiseInterface|null
     * @date 2025/5/26 下午2:16
     * @author 原点 467490186@qq.com
     */
    public function deleteAsync(string $serviceName, string $groupName, string $ip, int $port, array $optional = [])
    {
        return $this->client->requestAsync('DELETE', 'nacos/v1/ns/instance', [
            RequestOptions::QUERY => $this->filter(array_merge($optional, [
                'serviceName' => $serviceName,
                'groupName' => $groupName,
                'ip' => $ip,
                'port' => $port,
            ])),
        ]);
    }

    /**
     * 心跳
     * @param string $serviceName
     * @param array $beat
     * @param string|null $groupName
     * @param string|null $namespaceId
     * @param bool|null $ephemeral
     * @param bool $lightBeatEnabled
     * @param float $timeout
     * @return string
     * @date 2025/5/26 下午1:47
     * @author 原点 467490186@qq.com
     */
    public function beat(
        string $serviceName,
        array $beat = [],
        ?string $groupName = null,
        ?string $namespaceId = null,
        ?bool $ephemeral = null,
        bool $lightBeatEnabled = false,
        float $timeout = 5.0
    )
    {
        return $this->client->request('PUT', 'nacos/v1/ns/instance/beat', [
            RequestOptions::QUERY => $this->filter([
                'serviceName' => $serviceName,
                'ip' => $beat['ip'] ?? null,
                'port' => $beat['port'] ?? null,
                'groupName' => $groupName,
                'namespaceId' => $namespaceId,
                'ephemeral' => $ephemeral,
                'beat' => ! $lightBeatEnabled ? json_encode($beat) : '',
            ]),
            RequestOptions::TIMEOUT => $timeout,
        ]);
    }

    /**
     * @param string $serviceName
     * @param array $optional = [
     *     'groupName' => '',
     *     'namespaceId' => '',
     *     'clusters' => '', // 集群名称(字符串，多个集群用逗号分隔)
     *     'healthyOnly' => false,
     * ]
     * @return bool|string
     * @throws GuzzleException
     */
    public function list(string $serviceName, array $optional = [])
    {
        return $this->client->request('GET', 'nacos/v1/ns/instance/list', [
            RequestOptions::QUERY => $this->filter(array_merge($optional, [
                'serviceName' => $serviceName,
            ])),
        ]);
    }

    /**
     * @param array $input
     * @return array
     */
    protected function filter(array $input): array
    {
        $result = [];
        foreach ($input as $key => $value) {
            if ($value !== null) {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}