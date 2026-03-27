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

declare(strict_types=1);

namespace yuandian\WebmanNacos\Process;

use Psr\Http\Message\ResponseInterface;
use support\Log;
use Throwable;
use Workerman\Coroutine;
use Workerman\Timer;
use Workerman\Worker;
use yuandian\Container\Container;
use yuandian\WebmanNacos\Application;
use yuandian\WebmanNacos\NacosClient;

/**
 * nacos注册实例类
 */
class InstanceRegistrarProcess
{
    /**
     * @var array
     */
    protected array $instanceRegistrars = [];

    /**
     * @var array
     */
    protected array $heartbeatTimers = [];
    protected Application $client;

    /**
     * @var float
     */
    protected float $heartbeat;

    public function __construct()
    {
        $this->client = Container::getInstance()->make(NacosClient::class)->getClient();
        $this->heartbeat = (float) config('plugin.yuandian.webman-nacos.app.instance_heartbeat', 5.0);
    }


    /**
     * 心跳
     * @param string $name
     * @return void
     */
    protected function heartbeat(string $name): void
    {
        if (isset($this->instanceRegistrars[$name])) {
            [$serviceName, $ip, $port, $option] = $this->instanceRegistrars[$name];
            $option['ephemeral'] ??= false;
            // 仅对非永久实例进行心跳
            if (!$option['ephemeral']) {
                return;
            }
            $this->heartbeatTimers[$name] = Timer::add(
                $this->heartbeat,
                function () use ($name, $serviceName, $ip, $port, $option) {
                    try {
                        if (!$this->client->instance->beat(
                            $serviceName,
                            [
                                'ip'          => $ip,
                                'port'        => $port,
                                'serviceName' => $option['groupName'] . '@@' . $serviceName,
                            ],
                            $option['groupName'] ?? null,
                            $option['namespaceId'] ?? null,
                            $option['ephemeral'] ?? null,
                        )) {
                            Log::error("Nacos $name instance heartbeat failed");
                        }
                    } catch (Throwable $exception) {
                        Log::error("Nacos instance heartbeat failed: ." . $exception);
                    }
                }
            );
        }
    }


    public function onWorkerStart(Worker $worker)
    {
        $worker->count = 1;
        $instanceRegistrars = config('plugin.yuandian.webman-nacos.app.instance_registrars', []);
        if (empty($instanceRegistrars)) {
            return;
        }
        $this->register($instanceRegistrars);
    }


    public function onWorkerStop(Worker $worker)
    {
        try {
            foreach ($this->instanceRegistrars as $name => $instanceRegistrar) {
                // 移除心跳
                if (isset($this->heartbeatTimers[$name])) {
                    Timer::del($this->heartbeatTimers[$name]);
                }
                [$serviceName, $ip, $port, $option] = $instanceRegistrar;
                // 注销实例
                if (!$this->client->instance->delete(
                    $serviceName,
                    $option['groupName'] ?? null,
                    $ip,
                    $port,
                    [
                        'namespaceId' => $option['namespaceId'] ?? null,
                        'ephemeral'   => $option['ephemeral'] ?? null,
                    ]
                )) {
                    Log::error("Naocs $name instance delete failed");
                }
            }
        } catch (\Throwable $exception) {
            Log::error("Nacos instance delete failed: " . $exception);
        }
    }

    public function register(array $instanceRegistrars): void
    {
        try {
            foreach ($instanceRegistrars as $name => $instanceRegistrar) {
                // 拆解配置
                [$serviceName, $ip, $port, $option] = $instanceRegistrar;
                $ephemeral = $option['ephemeral'] ?? false;
                $enabled = $option['enabled'] ?? false;
                $option['ephemeral'] = $ephemeral ? 'true' : null;
                $option['enabled'] = $enabled ? 'true' : null;
                // 注册
                Coroutine::create(function () use ($ip, $port, $serviceName, $option, $instanceRegistrar, $name) {
                    try {
                        $response = $this->client->instance->registerAsync($ip, $port, $serviceName, $option)->wait();
                        if ($response->getStatusCode() === 200) {
                            $this->instanceRegistrars[$name] = $instanceRegistrar;
                            $this->heartbeat($name);
                        } else {
                            Log::error("Naocs $name instance register  failed ");
                        }
                    } catch (\Exception $exception) {
                        Log::error("Naocs $name instance register  failed :" . $exception);
                    }
                });
            }
        } catch (\Throwable $exception) {
            Log::error("Nacos instance delete failed: " . $exception);
        }
    }
}
