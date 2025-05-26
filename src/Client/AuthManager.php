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
use Webman\Config;

class AuthManager
{
    private string $accessToken = '';
    private int $tokenExpires = 0;
    private string $username;
    private string $password;
    protected array $serverList = [];
    protected int $serverIndex = 0;
    protected int $retry = 0;
    protected string $authUrl = '/nacos/v1/auth/login';

    public function __construct()
    {
        $conf = Config::get('plugin.yuandian.webman-nacos.app', []);
        $this->username = $conf['username'] ?? 'nacos';
        $this->password = $conf['password'] ?? 'nacos';
        $this->serverList = array_map('trim', explode(',', $conf['base_uri'] ?? 'http://127.0.0.1:8848'));
    }

    /**
     * 获取服务地址
     * @return string
     * @date 2025/5/26 下午2:16
     * @author 原点 467490186@qq.com
     */
    public function getServerUrl(): string
    {
        $server = $this->serverList[$this->serverIndex];
        $this->serverIndex = ($this->serverIndex + 1) % count($this->serverList);
        return $server;
    }

    /**
     * 刷新token
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Throwable
     * @date 2025/5/26 下午2:16
     * @author 原点 467490186@qq.com
     */
    public function refreshToken(): void
    {
        try {
            $client = new Client([
                'base_uri' => rtrim($this->getServerUrl(), '/') . '/',
                'timeout'  => 5.0,
            ]);

           
            $response = $client->post($this->authUrl, [
                'form_params' => [
                    'username' => $this->username,
                    'password' => $this->password,
                ]
            ]);
           
            $result = json_decode($response->getBody()->getContents(), true);
            if (empty($result['accessToken'])) {
                throw new \Exception("nacos access token is empty");
            }
            $this->accessToken = $result['accessToken'];
            $this->tokenExpires = time() + ($result['tokenTtl'] ?? 1800);
            $this->retry = 0;
            return;
        } catch (\Throwable $e) {
            $this->retry = $this->retry + 1;
            if ($this->retry > 3) {
                throw $e;
            }
            $this->refreshToken();
        }
    }

    /**
     * 获取token
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Throwable
     * @date 2025/5/26 下午2:16
     * @author 原点 467490186@qq.com
     */
    public function getAccessToken(): string
    {
        if ($this->tokenExpires == 0 || time() > $this->tokenExpires - 60) { // 提前60秒刷新
            $this->refreshToken();
        }
        return $this->accessToken;
    }
}