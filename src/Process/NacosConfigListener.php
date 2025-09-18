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
use yuandian\Container\Container;
use yuandian\WebmanNacos\NacosClient;

class NacosConfigListener
{
    public function onWorkerStart()
    {
        // 连接到本地Channel服务器
        Client::connect();
        $config_listeners = config('plugin.yuandian.webman-nacos.app.config_listeners', []);
        if (empty($config_listeners)) {
            return;
        }

        $Client = Container::getInstance()->make(NacosClient::class);
        $config = $Client->pull();
        // 订阅worker启动事件，推送一次初始化配置
        Client::on('config_request', function () use ($Client, $config) {
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
        $callback = function ($options) use ($Client) {
            $response = $Client->getClient()->config->get($options['dataId'], $options['group'], $options['tenant']);
            if ($response->getStatusCode() !== 200) {
                return;
            }
            $content = (string)$response->getBody();
            $contentMD5 = md5($content);
            $Client->setCacheMd5($options['configId'], $contentMD5);
            $config = $Client->decode($content, $options['type']);
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
        Timer::add(30, function () use ($Client, $callback) {
            $Client->Listener($callback);
        });
    }
}