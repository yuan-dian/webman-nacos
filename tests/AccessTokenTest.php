<?php

declare(strict_types=1);

namespace yuandian\WebmanNacos\Tests;

use yuandian\WebmanNacos\AccessToken;
use yuandian\WebmanNacos\Application;
use yuandian\WebmanNacos\Config;

class AccessTokenTest extends TestCase
{
    public function testGetAccessTokenReturnsNullWhenNoUsername(): void
    {
        $config = new Config(['password' => 'test_pass']);
        $app = new Application($config);
        $stub = new AccessTokenStub($app, $config);

        self::assertNull($stub->getAccessToken());
    }

    public function testGetAccessTokenReturnsNullWhenNoPassword(): void
    {
        $config = new Config(['username' => 'test_user']);
        $app = new Application($config);
        $stub = new AccessTokenStub($app, $config);

        self::assertNull($stub->getAccessToken());
    }

    public function testGetAccessTokenReturnsNullWhenNoCredentials(): void
    {
        $config = new Config([]);
        $app = new Application($config);
        $stub = new AccessTokenStub($app, $config);

        self::assertNull($stub->getAccessToken());
    }

    public function testIsExpiredReturnsTrueWhenTokenNotSet(): void
    {
        $config = new Config([]);
        $app = new Application($config);
        $stub = new AccessTokenStub($app, $config);

        self::assertTrue($stub->isExpiredPublic());
    }

    public function testIsExpiredReturnsTrueWhenTokenExpiresWithin60Seconds(): void
    {
        $config = new Config([]);
        $app = new Application($config);
        $stub = new AccessTokenStub($app, $config);

        $stub->setTokenData('test_token', time() + 30);

        self::assertTrue($stub->isExpiredPublic());
    }

    public function testIsExpiredReturnsTrueWhenTokenExpiresExactlyAt60Seconds(): void
    {
        $config = new Config([]);
        $app = new Application($config);
        $stub = new AccessTokenStub($app, $config);

        $stub->setTokenData('test_token', time() + 60);

        self::assertTrue($stub->isExpiredPublic());
    }

    public function testIsExpiredReturnsFalseWhenTokenIsValid(): void
    {
        $config = new Config([]);
        $app = new Application($config);
        $stub = new AccessTokenStub($app, $config);

        $stub->setTokenData('test_token', time() + 3600);

        self::assertFalse($stub->isExpiredPublic());
    }

    public function testIsExpiredReturnsFalseWhenTokenExpiresIn61Seconds(): void
    {
        $config = new Config([]);
        $app = new Application($config);
        $stub = new AccessTokenStub($app, $config);

        $stub->setTokenData('test_token', time() + 61);

        self::assertFalse($stub->isExpiredPublic());
    }

    public function testIsExpiredReturnsTrueWhenTokenAlreadyExpired(): void
    {
        $config = new Config([]);
        $app = new Application($config);
        $stub = new AccessTokenStub($app, $config);

        $stub->setTokenData('test_token', time() - 100);

        self::assertTrue($stub->isExpiredPublic());
    }
}

class AccessTokenStub
{
    use AccessToken;

    private ?string $accessToken = null;
    private int $expireTime = 0;

    public function __construct(
        protected Application $app,
        protected Config $config
    ) {
    }

    public function setTokenData(string $token, int $expireTime): void
    {
        $this->accessToken = $token;
        $this->expireTime = $expireTime;
    }

    public function isExpiredPublic(): bool
    {
        return $this->isExpired();
    }

    protected function handleResponse(array $response): array
    {
        return $response;
    }
}
