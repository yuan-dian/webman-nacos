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

use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use yuandian\WebmanNacos\AbstractProvider;

class OperatorProvider extends AbstractProvider
{
    public function getSwitches(): ResponseInterface
    {
        return $this->request('GET', 'nacos/v1/ns/operator/switches');
    }

    public function updateSwitches(string $entry, string $value, ?bool $debug = null): ResponseInterface
    {
        return $this->request('PUT', 'nacos/v1/ns/operator/switches', [
            RequestOptions::QUERY => $this->filter([
                'entry' => $entry,
                'value' => $value,
                'debug' => $debug,
            ]),
        ]);
    }

    public function getMetrics(): ResponseInterface
    {
        return $this->request('GET', 'nacos/v1/ns/operator/metrics');
    }

    public function getServers(?bool $healthy = null): ResponseInterface
    {
        return $this->request('GET', 'nacos/v1/ns/operator/servers', [
            RequestOptions::QUERY => $this->filter([
                'healthy' => $healthy,
            ]),
        ]);
    }

    public function getLeader(): ResponseInterface
    {
        return $this->request('GET', 'nacos/v1/ns/raft/leader');
    }
}