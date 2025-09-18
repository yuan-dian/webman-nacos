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

namespace yuandian\WebmanNacos\Provider\V2;

use GuzzleHttp\RequestOptions;
use JetBrains\PhpStorm\ArrayShape;
use Psr\Http\Message\ResponseInterface;
use yuandian\WebmanNacos\AbstractProvider;

class ServiceProvider extends AbstractProvider
{
    public function create(
        string $serviceName,
        #[ArrayShape([
            'groupName'        => '',
            'namespaceId'      => '',
            'protectThreshold' => 0.99,
            'metadata'         => '',
            'selector'         => '', // json字符串
        ])]
        array $optional = []
    ): ResponseInterface {
        return $this->request('POST', 'nacos/v2/ns/service', [
            RequestOptions::QUERY => $this->filter(array_merge($optional, [
                'serviceName' => $serviceName,
            ])),
        ]);
    }

    public function delete(
        string $serviceName,
        ?string $groupName = null,
        ?string $namespaceId = null
    ): ResponseInterface {
        return $this->request('DELETE', 'nacos/v2/ns/service', [
            RequestOptions::QUERY => $this->filter([
                'serviceName' => $serviceName,
                'groupName'   => $groupName,
                'namespaceId' => $namespaceId,
            ]),
        ]);
    }

    public function update(
        string $serviceName,
        #[ArrayShape([
            'groupName'        => '',
            'namespaceId'      => '',
            'protectThreshold' => 0.99,
            'metadata'         => '',
            'selector'         => '', // json字符串
        ])]
        array $optional = []
    ): ResponseInterface {
        return $this->request('PUT', 'nacos/v2/ns/service', [
            RequestOptions::QUERY => $this->filter(array_merge($optional, [
                'serviceName' => $serviceName,
            ])),
        ]);
    }

    public function detail(
        string $serviceName,
        ?string $groupName = null,
        ?string $namespaceId = null
    ): ResponseInterface {
        return $this->request('GET', 'nacos/v2/ns/service', [
            RequestOptions::QUERY => $this->filter([
                'serviceName' => $serviceName,
                'groupName'   => $groupName,
                'namespaceId' => $namespaceId,
            ]),
        ]);
    }

    public function list(
        int $pageNo,
        int $pageSize,
        ?string $groupName = null,
        ?string $namespaceId = null
    ): ResponseInterface {
        return $this->request('GET', 'nacos/v2/ns/service/list', [
            RequestOptions::QUERY => $this->filter([
                'pageNo'      => $pageNo,
                'pageSize'    => $pageSize,
                'groupName'   => $groupName,
                'namespaceId' => $namespaceId,
            ]),
        ]);
    }
}
