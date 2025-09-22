<?php
// +----------------------------------------------------------------------
// | 
// +----------------------------------------------------------------------
// | @copyright (c) 原点 All rights reserved.
// +----------------------------------------------------------------------
// | Author: 原点 <467490186@qq.com>
// +----------------------------------------------------------------------
// | Date: 2025/9/18
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace yuandian\WebmanNacos;


use GuzzleHttp\Promise\Utils;
use support\Log;
use Throwable;
use Workerman\Coroutine;

class NacosClient
{
    /**
     * 配置md5值缓存
     * @var array
     */
    private static array $cacheMd5 = [];

    public function setCacheMd5(string $key, string $md5): void
    {
        self::$cacheMd5[$key] = $md5;
    }

    public function getCacheMd5(string $key): string
    {
        return self::$cacheMd5[$key] ?? '';
    }

    public function __construct(protected Application $client)
    {
    }


    public function getClient(): Application
    {
        return $this->client;
    }

    public function pull(): array
    {
        $listener = \Webman\Config::get('plugin.yuandian.webman-nacos.app.config_listeners', []);

        $config = [];
        foreach ($listener as $key => $item) {
            $dataId = $item['dataId'] ?? '';
            $group = $item['group'] ?? '';
            $tenant = $item['tenant'] ?? null;
            $type = $item['type'] ?? null;
            $response = $this->client->config->get($dataId, $group, $tenant);
            $content = (string)$response->getBody();
            self::$cacheMd5[$key] = md5($content);
            if ($response->getStatusCode() !== 200) {
                Log::error(sprintf('The config of %s read failed from Nacos.', $key));
                continue;
            }
            $config[$key] = $this->decode($content, $type);
        }

        return $config;
    }

    public function listener(?callable $callable = null): void
    {
        $listener = \Webman\Config::get('plugin.yuandian.webman-nacos.app.config_listeners', []);
        foreach ($listener as $key => $item) {
            Coroutine::create(function () use ($item, $key, $callable) {
                while (true) {
                    try {
                        $options = [
                            'dataId'     => $item['dataId'] ?? '',
                            'group'      => $item['group'] ?? '',
                            'contentMD5' => self::$cacheMd5[$key] ?? null,
                            'tenant'     => $item['tenant'] ?? null,
                            'type'       => $item['type'] ?? null,
                            'configId'   => $key,
                        ];
                        $response = $this->client->config->listener($options);
                        if (!empty((string)$response->getBody())) {
                            if (is_callable($callable)) {
                                call_user_func($callable, $options);
                            }
                            Log::info("配置变更：" . $response->getBody());
                        }
                    } catch (Throwable $throwable) {
                        Log::error("监听配置变更失败：" . $throwable);
                    }
                }
            });
        }
    }


    public function listenerAsync(?callable $success = null, ?callable $error = null): void
    {
        $listener = \Webman\Config::get('plugin.yuandian.webman-nacos.app.config_listeners', []);
        $promises = [];
        foreach ($listener as $key => $item) {
            $options = [
                'dataId'     => $item['dataId'] ?? '',
                'group'      => $item['group'] ?? '',
                'contentMD5' => self::$cacheMd5[$key] ?? null,
                'tenant'     => $item['tenant'] ?? null,
                'type'       => $item['type'] ?? null,
                'configId'   => $key,
                'success'    => $success,
                'error'      => $error,
            ];
            $promises[] = $this->client->config->listenerAsync($options);
        }
        Utils::settle($promises)->wait();
    }

    public function decode(string $body, ?string $type = null): array|string
    {
        $type = strtolower((string)$type);

        return match ($type) {
            'json' => json_decode($body, true),
            'properties' => $this->parseProperties($body),
            'yml', 'yaml' => $this->parseYaml($body),
            'xml' => $this->parseXml($body),
            default => $body,
        };
    }

    private function parseXml($xml): array
    {
        $respObject = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOERROR);

        if ($respObject === false) {
            throw new \InvalidArgumentException('Syntax error.');
        }

        return json_decode(json_encode($respObject), true);
    }

    /**
     * 解析Yaml
     * @param string $content
     * @return array
     * @throws \Exception
     * @date 2025/7/16 上午10:38
     * @author 原点 467490186@qq.com
     */
    protected function parseYaml(string $content): array
    {
        if (extension_loaded('yaml')) {
            return yaml_parse($content);
        }
        if (class_exists('\Symfony\Component\Yaml\Yaml')) {
            return \Symfony\Component\Yaml\Yaml::parse($content);
        }
        throw new \Exception("解析Yaml失败，请安装yaml扩展或者symfony/yaml库");
    }

    /**
     * 解析properties格式配置
     * @param string $content
     * @return array
     * @date 2025/5/21 下午5:41
     * @author 原点 467490186@qq.com
     */
    protected function parseProperties(string $content): array
    {
        $result = [];
        foreach (explode("\n", $content) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            [$k, $v] = explode('=', $line, 2) + [null, null];
            if ($k !== null) {
                $result[trim($k)] = trim($v);
            }
        }
        return $result;
    }

    public function getValidNodes(
        string $serviceName,
        #[ArrayShape([
            'groupName'   => 'string',
            'namespaceId' => 'string',
            'clusters'    => 'string', // 集群名称(字符串，多个集群用逗号分隔)
            'healthyOnly' => 'bool',
        ])]
        array $optional = []
    ): array {
        $response = $this->client->instance->list($serviceName, $optional);
        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException((string)$response->getBody(), $response->getStatusCode());
        }

        $data = json_decode((string)$response->getBody(), true);
        $hosts = $data['hosts'] ?? [];
        return array_filter($hosts, function ($item) {
            return $item['valid'] ?? false;
        });
    }

}