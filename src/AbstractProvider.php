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

use Workerman\Coroutine;
use Workerman\Http\Client;
use Workerman\Http\Response;
use yuandian\Tools\http\HttpClient as SyncClient;

abstract class AbstractProvider
{
    use AccessToken;

    private static ?Client $asyncHttpClient = null;
    private static ?SyncClient $syncHttpClient = null;

    public function __construct(protected Application $app, protected Config $config)
    {
    }

    /**
     * Get or create the shared Workerman\Http\Client singleton (for async requests).
     */
    protected function asyncClient(): Client
    {
        if (self::$asyncHttpClient === null) {
            $httpConfig = $this->config->getHttpConfig();
            self::$asyncHttpClient = new Client($httpConfig);
        }
        return self::$asyncHttpClient;
    }

    /**
     * Get or create the shared yuandian/tools HttpClient singleton (for sync requests).
     * Uses curl, works in any context (no coroutine required).
     */
    protected function syncClient(): SyncClient
    {
        if (self::$syncHttpClient === null) {
            $httpConfig = $this->config->getHttpConfig();
            self::$syncHttpClient = SyncClient::create($httpConfig);
        }
        return self::$syncHttpClient;
    }

    /**
     * Wrap yuandian/tools Response into Workerman\Http\Response for type consistency.
     */
    protected function toWorkermanResponse(\yuandian\Tools\http\Response $response): Response
    {
        return new Response(
            $response->getStatusCode(),
            $response->getHeaders(),
            $response->getBody()
        );
    }

    /**
     * Synchronous request via yuandian/tools HttpClient (curl).
     * Works in any context — no coroutine required.
     * Returns Workerman\Http\Response for type consistency across providers.
     */
    public function request(string $method, string $uri, array $options = []): Response
    {
        // 判断协程环境直接使用Workerman\Http\Client非阻塞客户端
        if (Coroutine::isCoroutine()) {
            return $this->requestAsync($method, $uri, $options);
        } else {
            $url = $this->buildUrl($uri);
            $options['method'] = $method;
            $options = $this->init($uri, $options);
            $options = $this->normalizeOptions($url, $options);
            // Map options to yuandian/tools format
            $toolsOptions = [];
            if (isset($options['headers'])) {
                $toolsOptions['headers'] = $options['headers'];
            }
            if (isset($options['form_params'])) {
                $toolsOptions['form'] = $options['form_params'];
            }
            if (isset($options['body'])) {
                $toolsOptions['body'] = $options['body'];
            }

            return $this->toWorkermanResponse(
                $this->syncClient()->request($method, $url, $toolsOptions)
            );
        }
    }

    /**
     * Asynchronous request via Workerman\Http\Client (callback mode).
     * Callbacks passed via $options['success'] and $options['error'].
     */
    public function requestAsync(string $method, string $uri, array $options = []): mixed
    {
        $url = $this->buildUrl($uri);
        $options['method'] = $method;
        $options = $this->init($uri, $options);
        $options = $this->normalizeOptions($url, $options);

        // Map options to Workerman format
        if (isset($options['form_params'])) {
            $options['data'] = $options['form_params'];
            unset($options['form_params']);
        }
        if (isset($options['body'])) {
            $options['data'] = $options['body'];
            unset($options['body']);
        }

        return $this->asyncClient()->request($url, $options);
    }

    /**
     * Build full URL from base URI and relative path.
     */
    protected function buildUrl(string $uri): string
    {
        return rtrim($this->config->getBaseUri(), '/') . '/' . ltrim($uri, '/');
    }

    /**
     * Normalize options common to both sync and async clients:
     * - 'query' → append to URL as query string (bool values → 'true'/'false')
     */
    protected function normalizeOptions(string &$url, array $options): array
    {
        // Convert bool query values to 'true'/'false' strings for Nacos API
        if (isset($options['query']) && is_array($options['query'])) {
            array_walk($options['query'], function (&$value) {
                if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                }
            });
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator . http_build_query($options['query']);
        }
        unset($options['query']);

        return $options;
    }

    /**
     * Inject auth headers/query params.
     */
    protected function init(string $uri, array $options): array
    {
        if ($accessKey = $this->config->getAccessKey()) {
            $accessSecret = $this->config->getAccessSecret();

            if (str_contains($uri, '/ns/')) { // naming
                $options['headers']['ak'] = $accessKey;
                $signHeaders = $this->getNamingSignHeaders(
                    $options['query']['groupName'] ?? '',
                    $options['query']['serviceName'] ?? '',
                    $accessSecret
                );
                foreach ($signHeaders as $header => $value) {
                    $options['headers'][$header] = $value;
                }
            } else { // config
                $options['headers']['Spas-AccessKey'] = $accessKey;
                $signHeaders = $this->getMseSignHeaders($options['query'] ?? [], $accessSecret);
                foreach ($signHeaders as $header => $value) {
                    $options['headers'][$header] = $value;
                }
            }
        } else {
            if ($token = $this->getAccessToken()) {
                $options['query']['accessToken'] = $token;
            }
        }
        return $options;
    }

    protected function handleResponse(Response $response): array
    {
        $statusCode = $response->getStatusCode();
        $contents = (string)$response->getBody();

        if ($statusCode !== 200) {
            throw new \RuntimeException($contents, $statusCode);
        }

        return json_decode($contents, true);
    }

    protected function checkResponseIsOk(Response $response): bool
    {
        if ($response->getStatusCode() !== 200) {
            return false;
        }
        return (string)$response->getBody() === 'ok';
    }

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

    protected function getMseSignHeaders(array $data, string $secretKey): array
    {
        $group = $data['group'] ?? '';
        $tenant = $data['tenant'] ?? '';
        $timeStamp = round(microtime(true) * 1000);
        $signStr = '';

        if ($tenant) {
            $signStr .= "{$tenant}+";
        }
        if ($group) {
            $signStr .= "{$group}+";
        }
        $signStr .= "{$timeStamp}";

        return [
            'timeStamp'      => $timeStamp,
            'Spas-Signature' => base64_encode(hash_hmac('sha1', $signStr, $secretKey, true)),
        ];
    }

    protected function getNamingSignHeaders(string $groupName, string $serverName, string $secretKey): array
    {
        $timeStamp = round(microtime(true) * 1000);
        $signStr = (string)$timeStamp;

        if (!empty($serverName)) {
            if (str_contains($serverName, '@@') || empty($groupName)) {
                $signStr .= "@@{$serverName}";
            } else {
                $signStr .= "@@{$groupName}@@{$serverName}";
            }
        }

        return [
            'data'      => $signStr,
            'signature' => base64_encode(hash_hmac('sha1', $signStr, $secretKey, true)),
        ];
    }
}
