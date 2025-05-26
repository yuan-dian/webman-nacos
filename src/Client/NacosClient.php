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

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use support\Log;
use Workerman\Http\Client as AsyncClient;
use Workerman\Protocols\Http\Response;

class NacosClient
{
    /** @var AsyncClient|null */
    protected ?AsyncClient $httpClientAsync = null;
    protected ?Client $httpClient = null;

    public function __construct(private AuthManager $authManager)
    {
    }

    public function httpClient(): Client
    {
        $server = $this->authManager->getServerUrl();
        if(!$this->httpClient instanceof Client){
            $client = new Client([
                'base_uri' => rtrim($server, '/') . '/',
                'timeout'  => 5,
                'headers' => [
                    'Connection' => 'keep-alive'
                ]
            ]);
            $this->httpClient = $client;
        }
        return $this->httpClient;
    }

    public function request(string $method, string $uri, array $options = [], $timeout = 5.0): string
    {
        try {
            $client = $this->httpClient();

            if (!isset($options['query'])) {
                $options['query'] = [];
            }
            $options['query']['accessToken'] = $this->authManager->getAccessToken();
            $res = $client->request($method, ltrim($uri, '/'), $options);
            return $res->getBody()->getContents();
        } catch (\Throwable $exception) {
            Log::error($exception);
        }
        throw new \RuntimeException("Nacos HTTP request failed on all configured servers: $method $uri");
    }

    public function requestAsync(string $method, string $uri, array $options = [])
    {
        try {
            $client = $this->httpClient();
            if (!isset($options['query'])) {
                $options['query'] = [];
            }
            $options['query']['accessToken'] = $this->authManager->getAccessToken();
            return $client->requestAsync($method, ltrim($uri, '/'), $options);
        } catch (RequestException $exception) {
            log::error($exception);
        }
        throw new \RuntimeException("Nacos HTTP request failed on all configured servers: $method $uri");
    }

    public function httpClientAsync(): AsyncClient
    {
        if (!$this->httpClientAsync instanceof AsyncClient) {
            $config = [
                'connect_timeout' => config('plugin.yuandian.webman-nacos.app.long_pulling_interval', 30),
                'timeout'         => config('plugin.yuandian.webman-nacos.app.long_pulling_interval', 30) + 60,
            ];
            $this->httpClientAsync = new AsyncClient($config);
        }
        return $this->httpClientAsync;
    }

    public function requestAsyncUseEventLoop(string $method, string $uri, array $options = []): bool
    {
        try {
            $options['query']['accessToken'] = $this->authManager->getAccessToken();
            $queryString = http_build_query($options[RequestOptions::QUERY] ?? []);
            $headers = array_merge($options[RequestOptions::HEADERS] ?? [], [
                'Connection' => 'keep-alive'
            ]);
            $server = $this->authManager->getServerUrl();
            $this->httpClientAsync()->request(
                sprintf('%s/%s?%s', rtrim($server, '/'), ltrim($uri, '/'), $queryString),
                [
                    'method' => $method,
                    'version' => '1.1',
                    'headers' => $headers,
                    'data' => $options['data'] ?? [],
                    'success' => $options['success'] ?? function (Response $response) {},
                    'error' => $options['error'] ?? function (\Exception $exception) {}
                ]
            );
            return true;
        } catch (RequestException $exception) {
            Log::error($exception);
        }
        return false;
    }

    public function get(string $uri, array $query = []): string
    {
        return $this->request('GET', $uri, ['query' => $query]);
    }

    public function post(string $uri, array $form = []): string
    {
        return $this->request('POST', $uri, ['form_params' => $form]);
    }

    public function delete(string $uri, array $query = []): string
    {
        return $this->request('DELETE', $uri, ['query' => $query]);
    }
}