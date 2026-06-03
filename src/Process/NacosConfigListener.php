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
    /** @var array<string, array{total: int, ids: array<int, bool>}> Registered workers per process name */
    private static array $registeredWorkers = [];

    public function onWorkerStart(Worker $worker)
    {
        $config_listeners = config('plugin.yuandian.webman-nacos.app.config_listeners', []);
        if (empty($config_listeners)) {
            return;
        }
        // 连接到本地Channel服务器
        Client::connect();
        // 订阅 Worker 就绪事件，统计已就绪进程数
        Client::on('worker_ready', function ($data) {
            $name = $data['name'] ?? '';
            $id = $data['id'] ?? 0;
            $total = $data['total'] ?? 0;
            if (empty($name) || $total <= 0) {
                return;
            }
            if (!isset(self::$registeredWorkers[$name])) {
                self::$registeredWorkers[$name] = ['total' => $total, 'ids' => []];
            }
            self::$registeredWorkers[$name]['ids'][$id] = true;
        });

        $Client = Container::getInstance()->make(NacosClient::class);
        $config = $Client->pull();

        // 定时检查所有 Worker 是否就绪，全部就绪后才推送初始配置
        $timer_id = Timer::add(1, function () use (&$timer_id, $config, $Client) {
            if (empty(self::$registeredWorkers)) {
                return;
            }
            // 检查所有进程类型的 Worker 是否全部就绪
            foreach (self::$registeredWorkers as $name => $info) {
                if (count($info['ids']) < $info['total']) {
                    return; // 还有 Worker 未就绪
                }
            }
            // 全部就绪，推送初始配置
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
        });

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
        // 使用协程监听配置变更（Workerman\Http\Client 在协程模式下非阻塞）
        $Client->listener($callback);
    }
}