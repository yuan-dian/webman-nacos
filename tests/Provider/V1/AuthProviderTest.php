<?php

declare(strict_types=1);

namespace yuandian\WebmanNacos\Tests\Provider\V1;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use yuandian\WebmanNacos\Application;
use yuandian\WebmanNacos\Config;
use yuandian\WebmanNacos\Http\HttpClientAdapter;
use yuandian\WebmanNacos\Http\RequestOptions;
use yuandian\WebmanNacos\Provider\V1\AuthProvider;
use yuandian\WebmanNacos\Tests\TestCase;

class AuthProviderTest extends TestCase
{
    protected AuthProvider $authProvider;
    protected Application $application;
    protected Config $config;
    protected HttpClientAdapter|MockObject $httpClientMock;
    protected ResponseInterface|MockObject $responseMock;
    protected StreamInterface|MockObject $streamMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new Config([]);
        $this->application = new Application($this->config);

        $this->httpClientMock = $this->createMock(HttpClientAdapter::class);
        $this->responseMock = $this->createMock(ResponseInterface::class);
        $this->streamMock = $this->createMock(StreamInterface::class);

        $this->responseMock->method('getBody')->willReturn($this->streamMock);
        $this->responseMock->method('getStatusCode')->willReturn(200);

        $httpClientMock = $this->httpClientMock;
        $this->authProvider = new class($this->application, $this->config, $httpClientMock) extends AuthProvider {
            private HttpClientAdapter $mockClient;

            public function __construct(Application $app, Config $config, HttpClientAdapter $mockClient)
            {
                parent::__construct($app, $config);
                $this->mockClient = $mockClient;
            }

            public function client(): HttpClientAdapter
            {
                return $this->mockClient;
            }
        };
    }

    public function testLoginReturnsResponseInterface(): void
    {
        $this->httpClientMock->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'nacos/v1/auth/users/login',
                $this->callback(function ($options) {
                    return isset($options[RequestOptions::QUERY]['username']) &&
                           $options[RequestOptions::QUERY]['username'] === 'testUser' &&
                           isset($options[RequestOptions::FORM_PARAMS]['password']) &&
                           $options[RequestOptions::FORM_PARAMS]['password'] === 'testPassword';
                })
            )
            ->willReturn($this->responseMock);

        $response = $this->authProvider->login('testUser', 'testPassword');

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testLoginWithEmptyCredentialsReturnsResponseInterface(): void
    {
        $this->httpClientMock->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'nacos/v1/auth/users/login',
                $this->callback(function ($options) {
                    return isset($options[RequestOptions::QUERY]['username']) &&
                           $options[RequestOptions::QUERY]['username'] === '' &&
                           isset($options[RequestOptions::FORM_PARAMS]['password']) &&
                           $options[RequestOptions::FORM_PARAMS]['password'] === '';
                })
            )
            ->willReturn($this->responseMock);

        $response = $this->authProvider->login('', '');

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testLoginUsesCorrectHttpMethod(): void
    {
        $this->httpClientMock->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('POST'),
                $this->anything(),
                $this->anything()
            )
            ->willReturn($this->responseMock);

        $this->authProvider->login('user', 'pass');
    }

    public function testLoginUsesCorrectEndpoint(): void
    {
        $this->httpClientMock->expects($this->once())
            ->method('request')
            ->with(
                $this->anything(),
                $this->equalTo('nacos/v1/auth/users/login'),
                $this->anything()
            )
            ->willReturn($this->responseMock);

        $this->authProvider->login('user', 'pass');
    }

    public function testLoginUsesQueryParamsForUsername(): void
    {
        $this->httpClientMock->expects($this->once())
            ->method('request')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->callback(function ($options) {
                    return isset($options[RequestOptions::QUERY]['username']) &&
                           $options[RequestOptions::QUERY]['username'] === 'testUser';
                })
            )
            ->willReturn($this->responseMock);

        $this->authProvider->login('testUser', 'password');
    }

    public function testLoginUsesFormParamsForPassword(): void
    {
        $this->httpClientMock->expects($this->once())
            ->method('request')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->callback(function ($options) {
                    return isset($options[RequestOptions::FORM_PARAMS]['password']) &&
                           $options[RequestOptions::FORM_PARAMS]['password'] === 'testPassword';
                })
            )
            ->willReturn($this->responseMock);

        $this->authProvider->login('user', 'testPassword');
    }

    public function testLoginWithSpecialCharactersInCredentials(): void
    {
        $username = 'user@domain.com';
        $password = 'p@ssw0rd!#$%';

        $this->httpClientMock->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'nacos/v1/auth/users/login',
                $this->callback(function ($options) use ($username, $password) {
                    return $options[RequestOptions::QUERY]['username'] === $username &&
                           $options[RequestOptions::FORM_PARAMS]['password'] === $password;
                })
            )
            ->willReturn($this->responseMock);

        $response = $this->authProvider->login($username, $password);

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}