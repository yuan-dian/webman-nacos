<?php

declare(strict_types=1);

namespace yuandian\WebmanNacos\Tests;

use yuandian\WebmanNacos\Application;
use yuandian\WebmanNacos\Config;
use yuandian\WebmanNacos\Provider\V1\AuthProvider;
use yuandian\WebmanNacos\Provider\V1\ConfigProvider;
use yuandian\WebmanNacos\Provider\V1\InstanceProvider;
use yuandian\WebmanNacos\Provider\V1\OperatorProvider;
use yuandian\WebmanNacos\Provider\V1\ServiceProvider;

class ApplicationTest extends TestCase
{
    protected Config $config;
    protected Application $application;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = new Config([]);
        $this->application = new Application($this->config);
    }

    public function testConstructorAcceptsConfigObject(): void
    {
        $config = new Config(['base_uri' => 'http://test:8848/']);
        $app = new Application($config);

        self::assertInstanceOf(Application::class, $app);
    }

    public function testGetReturnsAuthProvider(): void
    {
        $provider = $this->application->auth;

        self::assertInstanceOf(AuthProvider::class, $provider);
    }

    public function testGetReturnsConfigProvider(): void
    {
        $provider = $this->application->config;

        self::assertInstanceOf(ConfigProvider::class, $provider);
    }

    public function testGetReturnsInstanceProvider(): void
    {
        $provider = $this->application->instance;

        self::assertInstanceOf(InstanceProvider::class, $provider);
    }

    public function testGetReturnsOperatorProvider(): void
    {
        $provider = $this->application->operator;

        self::assertInstanceOf(OperatorProvider::class, $provider);
    }

    public function testGetReturnsServiceProvider(): void
    {
        $provider = $this->application->service;

        self::assertInstanceOf(ServiceProvider::class, $provider);
    }

    public function testGetThrowsExceptionForInvalidAlias(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid_alias is invalid.');

        $provider = $this->application->invalid_alias;
    }

    public function testGetThrowsExceptionForEmptyName(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $provider = $this->application->{'invalid-name'};
    }

    public function testGetCachesProviderInstance(): void
    {
        $provider1 = $this->application->auth;
        $provider2 = $this->application->auth;

        self::assertSame($provider1, $provider2);
    }

    public function testResolveVersionClassReturnsV1Class(): void
    {
        $config = new Config(['version' => '1.0']);
        $app = new Application($config);

        $result = $app->resolveVersionClass(AuthProvider::class);

        self::assertEquals(AuthProvider::class, $result);
    }

    public function testResolveVersionClassReturnsV2Class(): void
    {
        $config = new Config(['version' => '2.0']);
        $app = new Application($config);

        $result = $app->resolveVersionClass(AuthProvider::class);

        self::assertIsString($result);
        self::assertStringContainsString('AuthProvider', $result);
    }

    public function testResolveVersionClassHandlesMajorVersion(): void
    {
        $config = new Config(['version' => '1.5.3']);
        $app = new Application($config);

        $result = $app->resolveVersionClass(ConfigProvider::class);

        self::assertEquals(ConfigProvider::class, $result);
    }

    public function testResolveVersionClassFallsBackToDefault(): void
    {
        $config = new Config(['version' => '99.0']);
        $app = new Application($config);

        $result = $app->resolveVersionClass(AuthProvider::class);

        self::assertEquals(AuthProvider::class, $result);
    }

    public function testAllProvidersAreAccessible(): void
    {
        $aliases = ['auth', 'config', 'instance', 'operator', 'service'];

        foreach ($aliases as $alias) {
            $provider = $this->application->$alias;
            self::assertNotNull($provider);
        }
    }
}
