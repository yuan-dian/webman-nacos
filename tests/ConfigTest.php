<?php

declare(strict_types=1);

namespace yuandian\WebmanNacos\Tests;

use yuandian\WebmanNacos\Config;

class ConfigTest extends TestCase
{
    public function testConstructorWithEmptyConfig(): void
    {
        $config = new Config([]);

        self::assertEquals('http://127.0.0.1:8848/', $config->getBaseUri());
        self::assertNull($config->getUsername());
        self::assertNull($config->getPassword());
        self::assertNull($config->getAccessKey());
        self::assertNull($config->getAccessSecret());
        self::assertEquals('1.0', $config->getVersion());
    }

    public function testConstructorWithFullConfig(): void
    {
        $configData = [
            'base_uri' => 'http://192.168.1.100:8848/',
            'username' => 'nacos_admin',
            'password' => 'secret_password',
            'access_key' => 'my_access_key',
            'access_secret' => 'my_access_secret',
            'version' => '2.0',
            'guzzle_config' => [
                'headers' => [
                    'charset' => 'UTF-8',
                    'X-Custom' => 'value',
                ],
                'http_errors' => true,
                'timeout' => 30,
            ],
        ];

        $config = new Config($configData);

        self::assertEquals('http://192.168.1.100:8848/', $config->getBaseUri());
        self::assertEquals('nacos_admin', $config->getUsername());
        self::assertEquals('secret_password', $config->getPassword());
        self::assertEquals('my_access_key', $config->getAccessKey());
        self::assertEquals('my_access_secret', $config->getAccessSecret());
        self::assertEquals('2.0', $config->getVersion());
    }

    public function testGetBaseUriReturnsDefaultValue(): void
    {
        $config = new Config([]);

        self::assertEquals('http://127.0.0.1:8848/', $config->getBaseUri());
    }

    public function testGetBaseUriReturnsCustomValue(): void
    {
        $config = new Config(['base_uri' => 'http://nacos.example.com:8848/']);

        self::assertEquals('http://nacos.example.com:8848/', $config->getBaseUri());
    }

    public function testGetUsernameReturnsNullByDefault(): void
    {
        $config = new Config([]);

        self::assertNull($config->getUsername());
    }

    public function testGetUsernameReturnsConfiguredValue(): void
    {
        $config = new Config(['username' => 'test_user']);

        self::assertEquals('test_user', $config->getUsername());
    }

    public function testGetPasswordReturnsNullByDefault(): void
    {
        $config = new Config([]);

        self::assertNull($config->getPassword());
    }

    public function testGetPasswordReturnsConfiguredValue(): void
    {
        $config = new Config(['password' => 'test_pass']);

        self::assertEquals('test_pass', $config->getPassword());
    }

    public function testGetAccessKeyReturnsNullByDefault(): void
    {
        $config = new Config([]);

        self::assertNull($config->getAccessKey());
    }

    public function testGetAccessKeyReturnsConfiguredValue(): void
    {
        $config = new Config(['access_key' => 'ak_12345']);

        self::assertEquals('ak_12345', $config->getAccessKey());
    }

    public function testGetAccessSecretReturnsNullByDefault(): void
    {
        $config = new Config([]);

        self::assertNull($config->getAccessSecret());
    }

    public function testGetAccessSecretReturnsConfiguredValue(): void
    {
        $config = new Config(['access_secret' => 'as_67890']);

        self::assertEquals('as_67890', $config->getAccessSecret());
    }

    public function testGetGuzzleConfigReturnsDefaultValues(): void
    {
        $config = new Config([]);
        $guzzleConfig = $config->getGuzzleConfig();

        self::assertIsArray($guzzleConfig);
        self::assertArrayHasKey('headers', $guzzleConfig);
        self::assertArrayHasKey('http_errors', $guzzleConfig);
        self::assertEquals('UTF-8', $guzzleConfig['headers']['charset']);
        self::assertFalse($guzzleConfig['http_errors']);
    }

    public function testGetGuzzleConfigReturnsCustomValues(): void
    {
        $customConfig = [
            'headers' => [
                'charset' => 'ISO-8859-1',
                'Accept' => 'application/json',
            ],
            'http_errors' => true,
            'timeout' => 60,
        ];

        $config = new Config(['guzzle_config' => $customConfig]);
        $guzzleConfig = $config->getGuzzleConfig();

        self::assertIsArray($guzzleConfig);
        self::assertEquals('ISO-8859-1', $guzzleConfig['headers']['charset']);
        self::assertEquals('application/json', $guzzleConfig['headers']['Accept']);
        self::assertTrue($guzzleConfig['http_errors']);
        self::assertEquals(60, $guzzleConfig['timeout']);
    }

    public function testGetVersionReturnsDefaultValue(): void
    {
        $config = new Config([]);

        self::assertEquals('1.0', $config->getVersion());
    }

    public function testGetVersionReturnsConfiguredValue(): void
    {
        $config = new Config(['version' => '3.1']);

        self::assertEquals('3.1', $config->getVersion());
    }

    public function testConstructorHandlesPartialConfig(): void
    {
        $config = new Config([
            'base_uri' => 'http://custom:8848/',
            'username' => 'partial_user',
        ]);

        self::assertEquals('http://custom:8848/', $config->getBaseUri());
        self::assertEquals('partial_user', $config->getUsername());
        self::assertNull($config->getPassword());
        self::assertNull($config->getAccessKey());
        self::assertNull($config->getAccessSecret());
        self::assertEquals('1.0', $config->getVersion());
    }

    public function testConstructorCastsValuesToString(): void
    {
        $config = new Config([
            'base_uri' => 123,
            'username' => 456,
            'version' => 789,
        ]);

        self::assertIsString($config->getBaseUri());
        self::assertIsString($config->getUsername());
        self::assertIsString($config->getVersion());
    }
}
