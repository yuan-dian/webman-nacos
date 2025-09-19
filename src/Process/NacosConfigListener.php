<?php
// +----------------------------------------------------------------------
// | 
// +----------------------------------------------------------------------
// | @copyright (c) 原点 All rights reserved.
// +----------------------------------------------------------------------
// | Author: 原点 <467490186@qq.com>
// +----------------------------------------------------------------------
// | Date: 2025/9/17
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace yuandian\WebmanNacos\Process;

use Webman\Channel\Client;
use Workerman\Timer;
use Workerman\Worker;
use yuandian\Container\Container;
use yuandian\WebmanNacos\NacosClient;

class NacosConfigListener
{
    // 工作进程就绪状态
    private static bool $workerReady = false;

    public function onWorkerStart(Worker $worker)
    {
        $config_listeners = config('plugin.yuandian.webman-nacos.app.config_listeners', []);
        if (empty($config_listeners)) {
            return;
        }
        // 连接到本地Channel服务器
        Client::connect();
        $Client = Container::getInstance()->make(NacosClient::class);
        $config = $Client->pull();
        // 配置变更回调
        $callback = function ($options) use ($Client) {
            $response = $Client->getClient()->config->get($options['dataId'], $options['group'], $options['tenant']);
            if ($response->getStatusCode() !== 200) {
                return;
            }
            $content = (string)$response->getBody();
            $contentMD5 = md5($content);
            $Client->setCacheMd5($options['configId'], $contentMD5);
            $config = $Client->decode($content, $options['type'] ?? null);
            if (empty($config)) {
                return;
            }
            $event_name = 'nacos_config_update';
            $data = [
                'configId'   => $options['configId'],
                'contentMD5' => $contentMD5,
                'config'     => $config
            ];
            Client::publish($event_name, $data);
        };
        // 监听配置变更
        if (!empty($worker->eventLoop) && in_array(
                $worker->eventLoop,
                ['Workerman\Events\Swow', 'Workerman\Events\Swoole'],
                true
            )) {
            $Client->listener($callback);
        } else {
            Timer::add(30, function () use ($Client, $callback) {
                $Client->listenerAsync($callback);
            });
        }
        // 订阅worker启动事件
        Client::on('worker_ready', function () {
            self::$workerReady = true;
        });
        // 添加一个定时器，确保订阅完成后才通知就绪
        $timer_id = Timer::add(1, function () use (&$timer_id, $config, $Client) {
            if (self::$workerReady) {
                Timer::del($timer_id);
                foreach ($config as $configId => $value) {
                    $event_name = 'nacos_config_update';
                    $data = [
                        'configId'   => $configId,
                        'contentMD5' => $Client->getCacheMd5($configId),
                        'config'     => $value
                    ];
                    Client::publish($event_name, $data);
                }
            }
        });
    }
}