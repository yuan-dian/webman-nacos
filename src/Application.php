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

use yuandian\WebmanNacos\Provider\V1\AuthProvider;
use yuandian\WebmanNacos\Provider\V1\ConfigProvider;
use yuandian\WebmanNacos\Provider\V1\InstanceProvider;
use yuandian\WebmanNacos\Provider\V1\OperatorProvider;
use yuandian\WebmanNacos\Provider\V1\ServiceProvider;

/**
 * @property AuthProvider $auth
 * @property ConfigProvider $config
 * @property InstanceProvider $instance
 * @property OperatorProvider $operator
 * @property ServiceProvider $service
 */
class Application
{
    protected array $alias = [
        'auth'     => AuthProvider::class,
        'config'   => ConfigProvider::class,
        'instance' => InstanceProvider::class,
        'operator' => OperatorProvider::class,
        'service'  => ServiceProvider::class,
    ];

    protected array $providers = [];

    public function __construct(protected Config $conf)
    {
    }

    public function __get($name)
    {
        if (!isset($name) || !isset($this->alias[$name])) {
            throw new \InvalidArgumentException("{$name} is invalid.");
        }

        if (isset($this->providers[$name])) {
            return $this->providers[$name];
        }

        $class = $this->resolveVersionClass($this->alias[$name]);
        return $this->providers[$name] = new $class($this, $this->conf);
    }

    public function resolveVersionClass(string $defaultClass): string
    {
        [$major] = array_pad(explode('.', $this->conf->getVersion()), 2, 0);

        $class = str_replace('V1', 'V' . $major, $defaultClass);

        return class_exists($class) ? $class : $defaultClass;
    }

}