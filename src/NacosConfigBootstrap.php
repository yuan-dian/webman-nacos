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
use ReflectionClass;
use support\Log;
use Throwable;
use Workerman\Worker;
use yuandian\Container\Container;
use yuandian\WebmanNacos\Annotation\NacosConfiguration;
use yuandian\WebmanNacos\Annotation\NacosValue;
use yuandian\WebmanNacos\Client\ConfigManage;

class NacosConfigBootstrap implements \Webman\Bootstrap
{
    private static array $cachedConfigClasses = [];
    private static array $reflectionCache = [];
    private static string $CACHE_FILE = '';
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
        self::$CACHE_FILE = runtime_path() . '/nacos_config_classes.cache';
        self::processAnnotations();
        $config_listeners = config('plugin.yuandian.webman-nacos.app.config_listeners', []);
        /**
         * @var ConfigManage $configManage ;
         */
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
        if (config('app.debug') || !file_exists(self::$CACHE_FILE)) {
            $classes = self::findProjectClasses();
            file_put_contents(self::$CACHE_FILE, serialize($classes));
        } else {
            $classes = unserialize(file_get_contents(self::$CACHE_FILE));
        }

        foreach ($classes as $class) {
            $reflection = static::getReflectionClass($class);;
            $attributes = $reflection->getAttributes(NacosConfiguration::class);

            if (!empty($attributes)) {
                /** @var NacosConfiguration $configAttr */
                $configAttr = $attributes[0]->newInstance();
                $key = $configAttr->configId;
                self::$cachedConfigClasses[$key][] = $class;
            }
        }
    }

    /**
     * 绑定属性值
     * @param object $instance
     * @param $config_id
     * @date 2025/5/26 下午2:24
     * @author 原点 467490186@qq.com
     */
    private static function bindProperties(object $instance, string $config_id): void
    {
        try {
            $reflection = static::getReflectionClass($instance);
            $attributes = $reflection->getAttributes(NacosConfiguration::class);
            if (empty($attributes)) {
                return;
            }
            $configAttr = $attributes[0]->newInstance();
            $prefix = $configAttr->prefix;
            foreach ($reflection->getProperties() as $property) {
                $attributes = $property->getAttributes(NacosValue::class);
                $name = $property->getName();
                $default_value = $property->getDefaultValue();
                if (!empty($attributes)) {
                    /** @var NacosValue $valueAttr */
                    $valueAttr = $attributes[0]->newInstance();
                    $name = $valueAttr->key;
                    $default_value = $valueAttr->default;
                }
                $key = $prefix . '.' . $name;
                $value = ConfigManage::getConfig($key, $default_value, $config_id);
                // todo 没有处理复杂类型，如对象赋值
                $property->setValue($instance, $value);
            }
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

    /**
     * 获取类的反射
     * @throws \ReflectionException
     */
    private static function getReflectionClass(string|object $object): ReflectionClass
    {
        $className = is_object($object) ? get_class($object) : $object;

        if (!isset(self::$reflectionCache[$className])) {
            self::$reflectionCache[$className] = new ReflectionClass($className);
        }

        return self::$reflectionCache[$className];
    }
}