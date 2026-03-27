<?php

declare(strict_types=1);
// +----------------------------------------------------------------------
// |
// +----------------------------------------------------------------------
// | @copyright (c) 原点 All rights reserved.
// +----------------------------------------------------------------------
// | Author: 原点 <467490186@qq.com>
// +----------------------------------------------------------------------
// | Date: 2025/9/18
// +----------------------------------------------------------------------

namespace yuandian\WebmanNacos;

use JetBrains\PhpStorm\ArrayShape;

class Config
{
    protected string $baseUri = 'http://127.0.0.1:8848/';

    protected ?string $username = null;

    protected ?string $password = null;

    protected ?string $accessKey = null;

    protected ?string $accessSecret = null;

    protected string $host = '127.0.0.1';

    protected int $port = 8848;

    protected ?string $version = '1.0';

    protected array $grpc = [
        'enable' => true,
        'heartbeat' => 10,
    ];

    protected array $guzzleConfig = [
        'headers' => [
            'charset' => 'UTF-8',
        ],
        'http_errors' => false,
    ];

    protected array $httpClientConfig = [
        'max_conn_per_addr' => 128,
        'keepalive_timeout' => 15,
        'connect_timeout' => 30,
        'timeout' => 30,
    ];

    public function __construct(
        #[ArrayShape([
            'base_uri' => 'string',
            'username' => 'string',
            'password' => 'string',
            'access_key' => 'string',
            'access_secret' => 'string',
            'guzzle_config' => 'array',
            'http_client_config' => 'array',
            'version' => 'string',
        ])]
        array $config = []
    ) {
        $conf = \Webman\Config::get('plugin.yuandian.webman-nacos.app', []);
        $config = array_merge($conf, $config);
        isset($config['base_uri']) && $this->baseUri = (string) $config['base_uri'];
        isset($config['username']) && $this->username = (string) $config['username'];
        isset($config['password']) && $this->password = (string) $config['password'];
        isset($config['access_key']) && $this->accessKey = (string) $config['access_key'];
        isset($config['access_secret']) && $this->accessSecret = (string) $config['access_secret'];
        isset($config['guzzle_config']) && $this->guzzleConfig = (array) $config['guzzle_config'];
        isset($config['http_client_config']) && $this->httpClientConfig = (array) $config['http_client_config'];
        isset($config['version']) && $this->version = (string) $config['version'];
    }

    public function getBaseUri(): string
    {
        return $this->baseUri;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getAccessKey(): ?string
    {
        return $this->accessKey;
    }

    public function getAccessSecret(): ?string
    {
        return $this->accessSecret;
    }

    public function getGuzzleConfig(): array
    {
        return $this->guzzleConfig;
    }

    public function getHttpClientConfig(): array
    {
        return $this->httpClientConfig;
    }

    public function getVersion(): string
    {
        return $this->version;
    }
}
