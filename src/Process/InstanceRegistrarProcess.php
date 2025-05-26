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

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise\Utils;
use Psr\Http\Message\ResponseInterface;
use support\Log;
use Workerman\Timer;
use Workerman\Worker;
use yuandian\Container\Container;
use yuandian\WebmanNacos\Client\InstanceManage;

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
    protected InstanceManage $client;

    /** @var int 进程重试间隔 */
    protected int $retry_interval;

    /**
     * @var float
     */
    protected float $heartbeat;

    public function __construct()
    {
        $this->client = Container::getInstance()->make(InstanceManage::class);
        $this->heartbeat = (float)config('plugin.yuandian.webman-nacos.app.instance_heartbeat', 5.0);
        $this->retry_interval = (int)config('plugin.yuandian.webman-nacos.app.process_retry_interval', 5);
    }

    /**
     * 心跳
     * @param string $name
     * @return void
     */
    protected function _heartbeat(string $name): void
    {
        if (isset($this->instanceRegistrars[$name])) {
            list($serviceName, $ip, $port, $option) = $this->instanceRegistrars[$name];
            if (isset($option['ephemeral'])) {
                $option['ephemeral'] = (is_string($option['ephemeral']) ? filter_var($option['ephemeral'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : (bool) $option['ephemeral'] );
            }
            // 仅对非永久实例进行心跳
            if ($option['ephemeral'] ?? false) {
                $this->heartbeatTimers[$name] = Timer::add($this->heartbeat, function () use ($name, $serviceName, $ip, $port, $option) {
                    try {
                        if(!$this->client->beat(
                            $serviceName,
                            array_filter([
                                    'ip' => $ip,
                                    'port' => $port,
                                    'serviceName' => $serviceName,
                                ] + $option, fn($value) => $value !== null),
                            $option['groupName'] ?? null,
                            $option['namespaceId'] ?? null,
                            $option['ephemeral'] ?? null,
                            false,
                            $this->heartbeat
                        )){
                            Log::error(
                                "Nacos instance heartbeat failed: [0].",
                                ['name' => $name, 'trace' => []]
                            );
                            sleep($this->retry_interval);
                            Worker::stopAll(0);
                        }
                    } catch (GuzzleException $exception){
                        Log::error(
                            "Nacos instance heartbeat failed: [{$exception->getCode()}] {$exception->getMessage()}.",
                            ['name' => $name, 'trace' => $exception->getTrace()]
                        );
                        sleep($this->retry_interval);
                        Worker::stopAll(0);
                    }
                });
            }

        }
    }

    /**
     * @inheritDoc
     */
    public function onWorkerStart(Worker $worker)
    {
        $worker->count = 1;
        try {
            if($instanceRegistrars = config('plugin.yuandian.webman-nacos.app.instance_registrars', [])){
                $promises = [];
                foreach ($instanceRegistrars as $name => $instanceRegistrar){
                    // 拆解配置
                    list($serviceName, $ip, $port, $option) = $instanceRegistrar;
                    // 注册
                    $promises[] = $this->client->registerAsync($ip, $port, $serviceName, $option)
                        ->then(function (ResponseInterface $response) use ($instanceRegistrar, $name) {
                            if($response->getStatusCode() === 200){
                                $this->instanceRegistrars[$name] = $instanceRegistrar;
                                $this->_heartbeat($name);
                            }else{
                                Log::error(
                                    "Naocs instance register failed: [0].",
                                    ['name' => $name, 'trace' => []]
                                );

                                sleep($this->retry_interval);
                                Worker::stopAll(0);
                            }
                        }, function (\Exception $exception) use ($instanceRegistrar, $name) {
                            Log::error(
                                "Nacos instance register failed: [{$exception->getCode()}] {$exception->getMessage()}.",
                                ['name' => $name, 'trace' => $exception->getTrace()]
                            );

                            sleep($this->retry_interval);
                            Worker::stopAll(0);
                        });
                }
                Utils::settle($promises)->wait();
            }

        } catch (GuzzleException $exception) {
            Log::error(
                "Nacos instance register failed: [{$exception->getCode()}] {$exception->getMessage()}.",
                ['name' => '#base', 'trace' => $exception->getTrace()]
            );

            sleep($this->retry_interval);
            Worker::stopAll(0);
        }
    }

    /**
     * @inheritDoc
     */
    public function onWorkerStop(Worker $worker)
    {
        try {
            foreach ($this->instanceRegistrars as $name => $instanceRegistrar) {
                // 移除心跳
                if(isset($this->heartbeatTimers[$name])){
                    Timer::del($this->heartbeatTimers[$name]);
                }
                list($serviceName, $ip, $port, $option) = $instanceRegistrar;
                // 注销实例
                if(!$this->client->deleteAsync(
                    $serviceName,
                    $option['groupName'] ?? null,
                    $ip,
                    $port,
                    [
                        'namespaceId' => $option['namespaceId'] ?? null,
                        'ephemeral' => $option['ephemeral'] ?? null
                    ]
                )){
                    Log::error(
                        "Naocs instance delete failed: [0].",
                        ['name' => $name, 'trace' => []]
                    );
                }
            }
        } catch (GuzzleException $exception) {
            Log::error(
                "Nacos instance delete failed: [{$exception->getCode()}] {$exception->getMessage()}.",
                ['name' => '#base', 'trace' => $exception->getTrace()]
            );
        }
    }
}
