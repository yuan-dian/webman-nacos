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

namespace yuandian\WebmanNacos\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

class HttpClientAdapter
{
    private \Workerman\Http\Client $client;

    public function __construct(array $config = [])
    {
        $this->client = new \Workerman\Http\Client($config);
    }

    public function request(string $method, string|UriInterface $uri, array $options = []): ResponseInterface
    {
        $uriString = $uri instanceof UriInterface ? (string) $uri : $uri;
        [$processedUri, $workermanOptions] = RequestOptions::convertToWorkermanOptions($options, $uriString);

        $workermanOptions['method'] = strtoupper($method);

        return $this->client->request($processedUri, $workermanOptions);
    }

    public function requestAsync(string $method, string|UriInterface $uri, array $options = []): AsyncResult
    {
        $uriString = $uri instanceof UriInterface ? (string) $uri : $uri;
        [$processedUri, $workermanOptions] = RequestOptions::convertToWorkermanOptions($options, $uriString);

        $workermanOptions['method'] = strtoupper($method);

        return new AsyncResult($this->client, $processedUri, $workermanOptions);
    }
}
