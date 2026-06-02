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

use Workerman\Http\Client;
use Workerman\Http\Response;

abstract class AbstractProvider
{
    use AccessToken;

    private static ?Client $httpClient = null;

    public function __construct(protected Application $app, protected Config $config)
    {
    }

    /**
     * Get or create the shared Workerman\Http\Client singleton.
     */
    protected function client(): Client
    {
        if (self::$httpClient === null) {
            $httpConfig = $this->config->getHttpConfig();
            self::$httpClient = new Client($httpConfig);
        }
        return self::$httpClient;
    }

    /**
     * Synchronous request (coroutine mode — non-blocking in Fiber/Swoole/Swow).
     * No success/error callbacks = Workerman returns Response directly (fiber-aware).
     */
    public function request(string $method, string $uri, array $options = []): Response
    {
        $url = $this->buildUrl($uri);
        $options['method'] = $method;
        $options = $this->init($uri, $options);
        $options = $this->normalizeOptions($url, $options);
        return $this->client()->request($url, $options);
    }

    /**
     * Asynchronous request (callback mode).
     * Callbacks passed via $options['success'] and $options['error'].
     */
    public function requestAsync(string $method, string $uri, array $options = []): void
    {
        $url = $this->buildUrl($uri);
        $options['method'] = $method;
        $options = $this->init($uri, $options);
        $options = $this->normalizeOptions($url, $options);
        $this->client()->request($url, $options);
    }

    /**
     * Build full URL from base URI and relative path.
     */
    protected function buildUrl(string $uri): string
    {
        return rtrim($this->config->getBaseUri(), '/') . '/' . ltrim($uri, '/');
    }

    /**
     * Normalize options for Workerman\Http\Client:
     * - 'query' → append to URL as query string (bool values → 'true'/'false')
     * - 'form_params' → rename to 'data'
     * - 'body' → rename to 'data'
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

        // Move form_params to data (Workerman uses 'data' key)
        if (isset($options['form_params'])) {
            $options['data'] = $options['form_params'];
            unset($options['form_params']);
        }

        // Move body string to data
        if (isset($options['body'])) {
            $options['data'] = $options['body'];
            unset($options['body']);
        }

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
