<?php

declare(strict_types=1);

namespace yuandian\WebmanNacos\Tests\Provider\V1;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use yuandian\WebmanNacos\Application;
use yuandian\WebmanNacos\Config;
use yuandian\WebmanNacos\Http\AsyncResult;
use yuandian\WebmanNacos\Http\HttpClientAdapter;
use yuandian\WebmanNacos\Http\RequestOptions;
use yuandian\WebmanNacos\Provider\V1\ConfigProvider;
use yuandian\WebmanNacos\Tests\TestCase;

class ConfigProviderTest extends TestCase
{
    protected ConfigProvider $configProvider;
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

        // 创建一个匿名类重写client()方法以返回模拟对象
        $httpClientMock = $this->httpClientMock;
        $this->configProvider = new class($this->application, $this->config, $httpClientMock) extends ConfigProvider {
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

    public function testGetReturnsResponseInterface(): void
    {
        $this->httpClientMock->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'nacos/v1/cs/configs',
                $this->callback(function ($options) {
                    return isset($options[RequestOptions::QUERY]['dataId']) &&
                           $options[RequestOptions::QUERY]['dataId'] === 'testDataId' &&
                           isset($options[RequestOptions::QUERY]['group']) &&
                           $options[RequestOptions::QUERY]['group'] === 'testGroup' &&
                           !isset($options[RequestOptions::QUERY]['tenant']);
                })
            )
            ->willReturn($this->responseMock);

        $response = $this->configProvider->get('testDataId', 'testGroup');

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testGetWithTenantReturnsResponseInterface(): void
    {
        $this->httpClientMock->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'nacos/v1/cs/configs',
                $this->callback(function ($options) {
                    return isset($options[RequestOptions::QUERY]['dataId']) &&
                           $options[RequestOptions::QUERY]['dataId'] === 'testDataId' &&
                           isset($options[RequestOptions::QUERY]['group']) &&
                           $options[RequestOptions::QUERY]['group'] === 'testGroup' &&
                           isset($options[RequestOptions::QUERY]['tenant']) &&
                           $options[RequestOptions::QUERY]['tenant'] === 'testTenant';
                })
            )
            ->willReturn($this->responseMock);

        $response = $this->configProvider->get('testDataId', 'testGroup', 'testTenant');

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testSetReturnsResponseInterface(): void
    {
        $this->httpClientMock->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'nacos/v1/cs/configs',
                $this->callback(function ($options) {
                    return isset($options[RequestOptions::FORM_PARAMS]['dataId']) &&
                           $options[RequestOptions::FORM_PARAMS]['dataId'] === 'testDataId' &&
                           isset($options[RequestOptions::FORM_PARAMS]['group']) &&
                           $options[RequestOptions::FORM_PARAMS]['group'] === 'testGroup' &&
                           isset($options[RequestOptions::FORM_PARAMS]['content']) &&
                           $options[RequestOptions::FORM_PARAMS]['content'] === 'testContent' &&
                           !isset($options[RequestOptions::FORM_PARAMS]['type']) &&
                           !isset($options[RequestOptions::FORM_PARAMS]['tenant']);
                })
            )
            ->willReturn($this->responseMock);

        
        $response = $this->configProvider->set('testDataId', 'testGroup', 'testContent');

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testSetWithTypeAndTenantReturnsResponseInterface(): void
    {
        $this->httpClientMock->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'nacos/v1/cs/configs',
                $this->callback(function ($options) {
                    return isset($options[RequestOptions::FORM_PARAMS]['dataId']) &&
                           $options[RequestOptions::FORM_PARAMS]['dataId'] === 'testDataId' &&
                           isset($options[RequestOptions::FORM_PARAMS]['group']) &&
                           $options[RequestOptions::FORM_PARAMS]['group'] === 'testGroup' &&
                           isset($options[RequestOptions::FORM_PARAMS]['content']) &&
                           $options[RequestOptions::FORM_PARAMS]['content'] === 'testContent' &&
                           isset($options[RequestOptions::FORM_PARAMS]['type']) &&
                           $options[RequestOptions::FORM_PARAMS]['type'] === 'yaml' &&
                           isset($options[RequestOptions::FORM_PARAMS]['tenant']) &&
                           $options[RequestOptions::FORM_PARAMS]['tenant'] === 'testTenant';
                })
            )
            ->willReturn($this->responseMock);

        
        $response = $this->configProvider->set('testDataId', 'testGroup', 'testContent', 'yaml', 'testTenant');

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testDeleteReturnsResponseInterface(): void
    {
        $this->httpClientMock->expects($this->once())
            ->method('request')
            ->with(
                'DELETE',
                'nacos/v1/cs/configs',
                $this->callback(function ($options) {
                    return isset($options[RequestOptions::QUERY]['dataId']) &&
                           $options[RequestOptions::QUERY]['dataId'] === 'testDataId' &&
                           isset($options[RequestOptions::QUERY]['group']) &&
                           $options[RequestOptions::QUERY]['group'] === 'testGroup' &&
                           !isset($options[RequestOptions::QUERY]['tenant']);
                })
            )
            ->willReturn($this->responseMock);

        
        $response = $this->configProvider->delete('testDataId', 'testGroup');

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testDeleteWithTenantReturnsResponseInterface(): void
    {
        $this->httpClientMock->expects($this->once())
            ->method('request')
            ->with(
                'DELETE',
                'nacos/v1/cs/configs',
                $this->callback(function ($options) {
                    return isset($options[RequestOptions::QUERY]['dataId']) &&
                           $options[RequestOptions::QUERY]['dataId'] === 'testDataId' &&
                           isset($options[RequestOptions::QUERY]['group']) &&
                           $options[RequestOptions::QUERY]['group'] === 'testGroup' &&
                           isset($options[RequestOptions::QUERY]['tenant']) &&
                           $options[RequestOptions::QUERY]['tenant'] === 'testTenant';
                })
            )
            ->willReturn($this->responseMock);

        
        $response = $this->configProvider->delete('testDataId', 'testGroup', 'testTenant');

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testListenerReturnsResponseInterface(): void
    {
        $expectedConfig = 'testDataId' . ConfigProvider::WORD_SEPARATOR .
                         'testGroup' . ConfigProvider::WORD_SEPARATOR .
                         'testMD5' . ConfigProvider::WORD_SEPARATOR .
                         'testTenant' . ConfigProvider::LINE_SEPARATOR;

        $this->httpClientMock->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'nacos/v1/cs/configs/listener',
                $this->callback(function ($options) use ($expectedConfig) {
                    return isset($options[RequestOptions::QUERY]['Listening-Configs']) &&
                           $options[RequestOptions::QUERY]['Listening-Configs'] === $expectedConfig &&
                           isset($options[RequestOptions::HEADERS]['Long-Pulling-Timeout']) &&
                           $options[RequestOptions::HEADERS]['Long-Pulling-Timeout'] === 30000;
                })
            )
            ->willReturn($this->responseMock);

        
        $response = $this->configProvider->listener([
            'dataId' => 'testDataId',
            'group' => 'testGroup',
            'contentMD5' => 'testMD5',
            'tenant' => 'testTenant',
        ]);

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testListenerAsyncReturnsAsyncResult(): void
    {
        $expectedConfig = 'testDataId' . ConfigProvider::WORD_SEPARATOR .
                         'testGroup' . ConfigProvider::WORD_SEPARATOR .
                         'testMD5' . ConfigProvider::WORD_SEPARATOR .
                         'testTenant' . ConfigProvider::LINE_SEPARATOR;

        $asyncResultMock = $this->createMock(AsyncResult::class);
        $asyncResultMock->method('then')->willReturnSelf();

        $this->httpClientMock->expects($this->once())
            ->method('requestAsync')
            ->with(
                'POST',
                'nacos/v1/cs/configs/listener',
                $this->callback(function ($options) use ($expectedConfig) {
                    return isset($options[RequestOptions::QUERY]['Listening-Configs']) &&
                           $options[RequestOptions::QUERY]['Listening-Configs'] === $expectedConfig &&
                           isset($options[RequestOptions::HEADERS]['Long-Pulling-Timeout']) &&
                           $options[RequestOptions::HEADERS]['Long-Pulling-Timeout'] === 30000;
                })
            )
            ->willReturn($asyncResultMock);

        
        $result = $this->configProvider->listenerAsync([
            'dataId' => 'testDataId',
            'group' => 'testGroup',
            'contentMD5' => 'testMD5',
            'tenant' => 'testTenant',
            'success' => function () {},
            'error' => function () {},
        ]);

        $this->assertInstanceOf(AsyncResult::class, $result);
    }

    public function testWordSeparatorConstant(): void
    {
        $this->assertEquals("\x02", ConfigProvider::WORD_SEPARATOR);
    }

    public function testLineSeparatorConstant(): void
    {
        $this->assertEquals("\x01", ConfigProvider::LINE_SEPARATOR);
    }

    public function testFilterMethodRemovesNullValues(): void
    {
        $reflection = new \ReflectionClass($this->configProvider);
        $method = $reflection->getMethod('filter');
        $method->setAccessible(true);

        $input = [
            'dataId' => 'test',
            'group' => 'testGroup',
            'tenant' => null,
            'type' => null,
        ];

        $result = $method->invoke($this->configProvider, $input);

        $this->assertArrayHasKey('dataId', $result);
        $this->assertArrayHasKey('group', $result);
        $this->assertArrayNotHasKey('tenant', $result);
        $this->assertArrayNotHasKey('type', $result);
    }
}