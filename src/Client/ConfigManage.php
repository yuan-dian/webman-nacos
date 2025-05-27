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

use GuzzleHttp\RequestOptions;
use support\Log;
use Workerman\Http\Response;
use Workerman\Timer;

/**
 * 配置管理
 */
class ConfigManage
{
    /**
     * 长轮询时间间隔
     * @var int
     */
    private static int $LONG_PULLING_INTERVAL = 30;
    /**
     * 长轮询超时时间，毫秒
     * @var int
     */
    private static int $LONG_PULLING_TIMEOUT = 30000;
    /**
     * 配置缓存
     * @var array
     */
    private static array $configCache = [];
    /**
     * 配置md5值缓存
     * @var array
     */
    private static array $cacheMd5 = [];
    /**
     * 配置监听列表
     * @var array
     */
    protected array $listeners = [];
    public const WORD_SEPARATOR = "\x02";

    public const LINE_SEPARATOR = "\x01";

    // 在构造函数注入AuthManager
    public function __construct(private NacosClient $httpClient)
    {
        self::$LONG_PULLING_INTERVAL = config('plugin.yuandian.webman-nacos.app.long_pulling_interval', 30);
        self::$LONG_PULLING_TIMEOUT = config('plugin.yuandian.webman-nacos.app.long_pulling_timeout', 30000);
    }


    /**
     * 获取配置
     * @param string|null $key
     * @param string|null $default
     * @param string $config_id
     * @return mixed
     * @date 2025/5/22 下午2:28
     * @author 原点 467490186@qq.com
     */
    public static function getConfig(?string $key = null, mixed $default = null, string $config_id = 'default'): mixed
    {
        $value = static::$configCache[$config_id] ?? [];
        if ($key === null) {
            return $value;
        }
        $segments = explode('.', $key);
        foreach ($segments as $segment) {
            if (is_array($value) && isset($value[$segment])) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }

        return $value;
    }

    /**
     * 查询并缓存配置
     * @param string $dataId
     * @param string $group
     * @param string $tenant
     * @param string $type
     * @param string $config_id
     * @return array
     * @date 2025/5/22 下午2:28
     * @author 原点 467490186@qq.com
     */
    public function fetchAndCache(
        string $dataId,
        string $group,
        string $tenant,
        string $type = 'json',
        string $config_id = 'default'
    ): array {
        $content = $this->httpClient->get('/nacos/v1/cs/configs', compact('dataId', 'group', 'tenant'));
        self::$cacheMd5[$config_id] = md5($content);
        $parsed = $this->parseConfig($content, $type);
        self::$configCache[$config_id] = $parsed;
        return $parsed;
    }

    /**
     * 提交配置
     * @param string $dataId
     * @param string $group
     * @param string $tenant
     * @param string $content
     * @return bool
     * @date 2025/5/22 下午2:28
     * @author 原点 467490186@qq.com
     */
    public function publishConfig(string $dataId, string $group, string $tenant, string $content): bool
    {
        $this->httpClient->post('/nacos/v1/cs/configs', compact('dataId', 'group', 'tenant', 'content'));
        return true;
    }

    /**
     * 删除配置
     * @param string $dataId
     * @param string $group
     * @param string $tenant
     * @param string $config_id
     * @return bool
     * @date 2025/5/22 下午2:29
     * @author 原点 467490186@qq.com
     */
    public function deleteConfig(string $dataId, string $group, string $tenant, string $config_id = 'default'): bool
    {
        $this->httpClient->delete('/nacos/v1/cs/configs', compact('dataId', 'group', 'tenant'));
        unset(self::$configCache[$config_id]);
        return true;
    }

    /**
     * 增加监听
     * @param string $dataId
     * @param string $group
     * @param string $tenant
     * @param string $type
     * @param callable|null $callback
     * @param string $config_id
     * @date 2025/5/22 下午2:29
     * @author 原点 467490186@qq.com
     */
    public function addListener(
        string $dataId,
        string $group,
        string $tenant,
        string $type = 'yaml',
        ?callable $callback = null,
        string $config_id = 'default',
    ): void {
        $this->listeners[$config_id] = compact('dataId', 'group', 'tenant', 'type', 'callback');
    }

    /**
     * 解析配置
     * @param string $content
     * @param string $type
     * @return array
     * @date 2025/5/22 下午2:29
     * @author 原点 467490186@qq.com
     */
    protected function parseConfig(string $content, string $type): array
    {
        return match (strtolower($type)) {
            'yaml', 'yml' => \yaml_parse($content),
            'properties' => $this->parseProperties($content),
            default => json_decode($content, true) ?? [],
        };
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

    /**
     * 启动监听
     * @date 2025/5/21 下午5:40
     * @author 原点 467490186@qq.com
     */
    public function startListener(): void
    {
        foreach ($this->listeners as $config_id => $listener) {
            $dataId = $listener['dataId'];
            $group = $listener['group'];
            $tenant = $listener['tenant'];
            $type = $listener['type'] ?? 'json';
            $callback = $listener['callback'];
            // 初始化配置
            $this->fetchAndCache($dataId, $group, $tenant, $type);
            if (is_callable($callback)) {
                call_user_func($callback, $config_id);
            }
            // 使用长轮询监听配置
            Timer::add(
                self::$LONG_PULLING_INTERVAL,
                function () use ($dataId, $group, $tenant, $callback, $type, $config_id) {
                    try {
                        $this->startLongPolling($dataId, $group, $tenant, $type, $config_id, $callback);
                    } catch (\Throwable $e) {
                        Log::error($e->getMessage());
                    }
                }
            );
        }
    }

    /**
     * 启动长轮询
     * @param string $dataId
     * @param string $group
     * @param string $tenant
     * @param string $type
     * @param string $config_id
     * @param callable|null $callback
     * @date 2025/5/22 下午2:29
     * @author 原点 467490186@qq.com
     */
    private function startLongPolling(
        string $dataId,
        string $group,
        string $tenant,
        string $type,
        string $config_id,
        ?callable $callback = null
    ): void {
        $contentMD5 = self::$cacheMd5[$config_id] ?? '';
        try {
            $ListeningConfigs = $dataId . self::WORD_SEPARATOR .
                $group . self::WORD_SEPARATOR .
                $contentMD5 . self::WORD_SEPARATOR .
                $tenant . self::LINE_SEPARATOR;
            $options = [
                RequestOptions::QUERY => [
                    'Listening-Configs' => $ListeningConfigs,
                ],
                RequestOptions::HEADERS => [
                    'Long-Pulling-Timeout' => self::$LONG_PULLING_TIMEOUT,
                ],
            ];
            $options['success'] = function (Response $response) use (
                $dataId,
                $group,
                $tenant,
                $type,
                $config_id,
                $callback
            ) {
                if ($response->getStatusCode() === 200) {
                    if (!empty($response->getBody()->getContents())) {
                        $this->fetchAndCache($dataId, $group, $tenant, $type, $config_id);
                        if (is_callable($callback)) {
                            call_user_func($callback, $config_id);
                        }
                        Log::info("配置变更：" . $response->getBody()->getContents());
                    }

                } else {
                    Log::error("配置变更失败：" . $response);
                    $this->startLongPolling($dataId, $group, $tenant, $type, $config_id, $callback);
                }
            };

            $options['error'] = function ($response) {
                Log::error("长轮询更新配置失败：" . (string)$response);
            };

            $this->httpClient->requestAsyncUseEventLoop(
                'POST',
                '/nacos/v1/cs/configs/listener',
                $options
            );
        } catch (\Throwable $exception) {
            Log::error("长轮询更新配置失败：" . $exception->getMessage());
        }
    }
}