<?php
// +----------------------------------------------------------------------
// |
// +----------------------------------------------------------------------
// | @copyright (c) 原点 All rights reserved.
// +----------------------------------------------------------------------
// | Author: 原点 <467490186@qq.com>
// +----------------------------------------------------------------------
// | Date: 2026/2/27
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace yuandian\WebmanNacos;

use InvalidArgumentException;
use Webman\Config;
use Webman\Finder\Finder;

use function is_array;
use function is_dir;
use function preg_match;
use function preg_quote;
use function scandir;
use function str_starts_with;

/**
 * ControllerFinder
 *
 * Discover controller files in main app and/or plugins.
 *
 * Scope examples:
 * - null        : main app only
 * - '*'         : main app + all enabled plugins
 * - 'plugin.*'  : all enabled plugins
 * - 'plugin.xxx': single plugin (strict: throws when plugin directory/config missing)
 */
class NacosConfigFinder
{
    /**
     * Find NacosConig files by scope.
     *
     * @param string|null $scope
     * @return FileInfo[]
     */
    public static function files(?string $scope = null): array
    {
        $roots = static::resolveRoots($scope);
        if (!$roots) {
            return [];
        }

        $resultsByPath = [];
        foreach ($roots as $root) {
            $dir = $root['dir'];
            $suffix = $root['suffix'] ?? '';
            $nacosConfigFiles = static::findNacosConfigFiles($dir, $suffix);
            foreach ($nacosConfigFiles as $file) {
                $resultsByPath[$file->getPathname()] = $file;
            }
        }

        return array_values($resultsByPath);
    }

    /**
     * Resolve search roots by scope.
     *
     * @param string|null $scope
     * @return array<int, array{dir: string, suffix: string}>
     */
    protected static function resolveRoots(?string $scope): array
    {
        if ($scope === null) {
            return static::mainAppRoots();
        }

        if ($scope === '*') {
            return array_merge(static::mainAppRoots(), static::allPluginRoots());
        }

        if ($scope === 'plugin.*') {
            return static::allPluginRoots();
        }

        if (str_starts_with($scope, 'plugin.')) {
            $plugin = substr($scope, strlen('plugin.'));
            if ($plugin === '' || $plugin === '*') {
                throw new InvalidArgumentException("Invalid NacosConfig scope: $scope");
            }
            return static::singlePluginRoots($plugin);
        }

        throw new InvalidArgumentException("Invalid NacosConfig scope: $scope");
    }

    /**
     * Main app roots.
     *
     * @return array<int, array{dir: string, suffix: string}>
     */
    protected static function mainAppRoots(): array
    {
        $roots = [];
        $appRoot = app_path();
        if (is_dir($appRoot)) {
            $roots[] = [
                'dir'    => $appRoot,
                'suffix' => (string)Config::get('plugin.yuandian.webman-nacos.config_suffix', ''),
            ];
        }
        return $roots;
    }

    /**
     * Roots for all enabled plugins.
     *
     * Rule (A): if plugin app config is missing/empty, skip it silently.
     *
     * @return array<int, array{dir: string, suffix: string}>
     */
    protected static function allPluginRoots(): array
    {
        $roots = [];
        $pluginBase = base_path('plugin');
        if (!is_dir($pluginBase)) {
            return [];
        }

        foreach (scandir($pluginBase) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if (!static::isValidIdentifier($entry)) {
                continue;
            }

            $pluginDir = $pluginBase . DIRECTORY_SEPARATOR . $entry;
            if (!is_dir($pluginDir)) {
                continue;
            }

            // Only load enabled plugins (same semantics as Route::loadAnnotationRoutes()).
            $pluginAppConfig = Config::get("plugin.$entry.app");
            if (!$pluginAppConfig) {
                continue;
            }

            $pluginAppDir = $pluginDir . DIRECTORY_SEPARATOR . 'app';
            if (!is_dir($pluginAppDir)) {
                continue;
            }

            $roots[] = [
                'dir'    => $pluginAppDir,
                'suffix' => is_array($pluginAppConfig)
                    ? (string)($pluginAppConfig['config_suffix'] ?? '')
                    : (string)Config::get('plugin.yuandian.webman-nacos.config_suffix', ''),
            ];
        }

        return $roots;
    }

    /**
     * Roots for a single plugin (strict).
     *
     * @param string $plugin
     * @return array<int, array{dir: string, suffix: string}>
     */
    protected static function singlePluginRoots(string $plugin): array
    {
        if (!static::isValidIdentifier($plugin)) {
            throw new InvalidArgumentException("Invalid plugin identifier: $plugin");
        }

        $pluginBase = base_path('plugin');
        $pluginDir = $pluginBase . DIRECTORY_SEPARATOR . $plugin;
        if (!is_dir($pluginDir)) {
            throw new InvalidArgumentException("Plugin directory not found: $plugin");
        }

        $pluginAppConfig = Config::get("plugin.$plugin.app");
        if (!$pluginAppConfig) {
            throw new InvalidArgumentException("Plugin app config not found or empty: plugin.$plugin.app");
        }

        $pluginAppDir = $pluginDir . DIRECTORY_SEPARATOR . 'app';
        if (!is_dir($pluginAppDir)) {
            throw new InvalidArgumentException("Plugin app directory not found: plugin/$plugin/app");
        }

        return [
            [
                'dir'    => $pluginAppDir,
                'suffix' => is_array($pluginAppConfig)
                    ? (string)($pluginAppConfig['config_suffix'] ?? '')
                    : (string)Config::get('plugin.yuandian.webman-nacos.config_suffix', ''),
            ]
        ];
    }

    /**
     * Find NacosConfig files.
     *
     * @param string $rootDir
     * @param string $nacosConfigSuffix
     * @return FileInfo[]
     */
    protected static function findNacosConfigFiles(string $rootDir, string $configSuffix = ''): array
    {
        $configPathRegex = $configSuffix !== ''
            ? ('/(^|[\/\\\\])config[\/\\\\].*' . preg_quote($configSuffix, '/') . '\.php$/i')
            : '/(^|[\/\\\\])config[\/\\\\].+\.php$/i';

        $finder = Finder::in($rootDir)
            ->files()
            ->path($configPathRegex)
            ->hasAttributes(true)
            ->typeIn(['class'])
            ->psr4(true);

        return $finder->find();
    }

    /**
     * Is valid identifier (plugin name).
     *
     * @param string $name
     * @return bool
     */
    protected static function isValidIdentifier(string $name): bool
    {
        return $name !== '' && (bool)preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name);
    }
}

