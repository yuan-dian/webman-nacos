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

use Workerman\Http\Response;
use yuandian\WebmanNacos\AbstractProvider;

class OperatorProvider extends AbstractProvider
{
    public function getSwitches(): Response
    {
        return $this->request('GET', 'nacos/v2/ns/operator/switches');
    }

    public function updateSwitches(string $entry, string $value, ?bool $debug = null): Response
    {
        return $this->request('PUT', 'nacos/v2/ns/operator/switches', [
            'query' => $this->filter([
                'entry' => $entry,
                'value' => $value,
                'debug' => $debug,
            ]),
        ]);
    }

    public function getMetrics(): Response
    {
        return $this->request('GET', '/nacos/v2/ns/operator/metrics');
    }

    public function getServers(?bool $healthy = null): Response
    {
        return $this->request('GET', 'nacos/v1/ns/operator/servers', [
            'query' => $this->filter([
                'healthy' => $healthy,
            ]),
        ]);
    }

    public function getLeader(): Response
    {
        return $this->request('GET', 'nacos/v1/ns/raft/leader');
    }
}
