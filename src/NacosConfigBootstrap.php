<?php
// +----------------------------------------------------------------------
// | 
// +----------------------------------------------------------------------
// | @copyright (c) 原点 All rights reserved.
// +----------------------------------------------------------------------
// | Author: 原点 <467490186@qq.com>
// +----------------------------------------------------------------------
// | Date: 2025/5/13
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace yuandian\WebmanNacos;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use support\Log;
use Throwable;
use Webman\Channel\Client;
use Workerman\Timer;
use Workerman\Worker;
use yuandian\Container\Container;
use yuandian\Tools\bean\BeanUtil;
use yuandian\Tools\reflection\ClassReflector;
use yuandian\WebmanNacos\Annotation\NacosConfiguration;

class NacosConfigBootstrap implements \Webman\Bootstrap
{
    private static array $cachedConfigClasses = [];
    private static bool $initialized = false;
    private static bool $initializedConfig = false;

    /**
     * 配置md5值缓存
     * @var array
     */
    private static array $cacheMd5 = [];

    /**
     * 服务启动
     * @param Worker|null $worker
     * @throws \ReflectionException
     * @date 2025/5/26 下午2:22
     * @author 原点 467490186@qq.com
     */
    public static function start(?Worker $worker)
    {
        $listen_processes_name = config('plugin.yuandian.webman-nacos.app.listen_processes_name', []);
        if (self::$initialized || !in_array($worker->name, $listen_processes_name)) {
            return;
        }
        self::$initialized = true;
        // 连接到本地Channel服务器
        Client::connect();
        self::processAnnotations();
        // 订阅配置变更事件
        Client::on('nacos_config_update', function ($data) {
            self::$initializedConfig = true;
            $configId = $data['configId'];
            $contentMD5 = $data['contentMD5'];
            // 判断内容是否有变化
            if (isset(self::$cacheMd5[$configId]) && self::$cacheMd5[$configId] === $contentMD5) {
                return;
            }
            if (empty($data['config'])) {
                return;
            }
            self::$cacheMd5[$configId] = $contentMD5;
            $classes = self::$cachedConfigClasses[$configId] ?? [];
            foreach ($classes as $class) {
                $instance = Container::getInstance()->make($class);
                self::bindProperties($instance, $data['config']);
            }
        });
        // 添加一个延迟，确保订阅完成后才通知就绪
        $timer_id = Timer::add(1, function () use (&$timer_id) {
            if (!self::$initializedConfig) {
                // 通知监听进程，当前Worker已准备就绪
                Client::publish('config_request', []);
            }
            if (self::$initializedConfig) {
                Timer::del($timer_id);
            }
        });
    }

    /**
     * 扫描注解
     * @throws \ReflectionException
     * @date 2025/5/26 下午2:22
     * @author 原点 467490186@qq.com
     */
    private static function processAnnotations(): void
    {
        $classes = self::findProjectClasses();

        foreach ($classes as $class) {
            $reflection = new ClassReflector($class);
            $config = $reflection->getAttribute(NacosConfiguration::class);
            if (empty($config)) {
                continue;
            }
            $key = $config->configId;
            self::$cachedConfigClasses[$key][] = $class;
        }
    }

    /**
     * 绑定属性值
     * @param object $instance
     * @param array $config
     * @date 2025/5/26 下午2:24
     * @author 原点 467490186@qq.com
     */
    private static function bindProperties(object $instance, array $config): void
    {
        try {
            $reflection = new ClassReflector($instance);
            $nacosConfig = $reflection->getAttribute(NacosConfiguration::class);
            if (empty($nacosConfig)) {
                return;
            }
            $data = self::getConfig($config, $nacosConfig->prefix, []);
            BeanUtil::arrayToObject($data, $instance);
        } catch (Throwable $throwable) {
            Log::error($throwable);
        }
    }

    /**
     * 获取配置
     * @param array $value
     * @param string|null $key
     * @param mixed|null $default
     * @return mixed
     * @date 2025/9/17 下午3:22
     * @author 原点 467490186@qq.com
     */
    public static function getConfig(array $value = [], ?string $key = null, mixed $default = null): mixed
    {
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
     * 扫描需要自动注册的配置类
     * @return array
     * @date 2025/5/26 下午2:23
     * @author 原点 467490186@qq.com
     */
    private static function findProjectClasses(): array
    {
        $classes = [];
        $dirs = config('plugin.yuandian.webman-nacos.app.scan_dirs', [app_path() . '/config']);

        foreach ($dirs as $dir) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $className = self::getClassNameFromFile($file->getPathname());
                    if ($className && class_exists($className)) {
                        $classes[] = $className;
                    }
                }
            }
        }

        return $classes;
    }

    /**
     * 获取类的命名空间
     * @param string $filePath
     * @return string|null
     * @date 2025/5/26 下午2:23
     * @author 原点 467490186@qq.com
     */
    private static function getClassNameFromFile(string $filePath): ?string
    {
        $content = file_get_contents($filePath);
        if (preg_match(
            '/\bnamespace\s+(.+?);.*?((abstract|final)\s+)?(class|interface)\s+(\w+)/s',
            $content,
            $matches
        )) {
            return $matches[1] . '\\' . $matches[5];
        }
        return null;
    }
}