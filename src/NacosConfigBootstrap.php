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
use Workerman\Worker;
use yuandian\Container\Container;
use yuandian\Tools\bean\BeanUtil;
use yuandian\Tools\reflection\ClassReflector;
use yuandian\WebmanNacos\Annotation\NacosConfiguration;
use yuandian\WebmanNacos\Client\ConfigManage;

class NacosConfigBootstrap implements \Webman\Bootstrap
{
    private static array $cachedConfigClasses = [];
    private static bool $initialized = false;

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
        self::processAnnotations();
        $config_listeners = config('plugin.yuandian.webman-nacos.app.config_listeners', []);
        $configManage = Container::getInstance()->make(ConfigManage::class);
        foreach ($config_listeners as $config_id => $config_listener) {
            $configManage->addListener(
                $config_listener['dataId'] ?? config('app.name', 'webman') . '.yaml',
                $config_listener['group'] ?? 'DEFAULT_GROUP',
                $config_listener['tenant'] ?? 'public',
                $config_listener['type'] ?? 'yaml',
                function ($config_id) {
                    $classes = self::$cachedConfigClasses[$config_id] ?? [];
                    foreach ($classes as $class) {
                        $instance = Container::getInstance()->make($class);
                        self::bindProperties($instance, $config_id);
                    }
                },
                $config_id
            );
        }
        $configManage->startListener();
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
     * @param string $config_id
     * @date 2025/5/26 下午2:24
     * @author 原点 467490186@qq.com
     */
    private static function bindProperties(object $instance, string $config_id): void
    {
        try {
            $reflection = new ClassReflector($instance);
            $nacosConfig = $reflection->getAttribute(NacosConfiguration::class);
            if (empty($nacosConfig)) {
                return;
            }
            $data = ConfigManage::getConfig($nacosConfig->prefix, [], $config_id);
            BeanUtil::arrayToObject($data, $instance);
        } catch (Throwable $throwable) {
            Log::error($throwable);
        }
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