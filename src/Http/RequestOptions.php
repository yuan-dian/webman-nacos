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

namespace yuandian\WebmanNacos\Http;

/**
 * RequestOptions常量映射类
 * 将GuzzleHttp\RequestOptions常量映射到workerman/http-client格式
 */
class RequestOptions
{
    /**
     * 查询参数映射
     * GuzzleHttp: RequestOptions::QUERY => ['key' => 'value']
     * Workerman: 需要将查询参数附加到URL
     */
    public const QUERY = 'query';

    /**
     * 表单参数映射
     * GuzzleHttp: RequestOptions::FORM_PARAMS => ['key' => 'value']
     * Workerman: 'data' => ['key' => 'value']
     */
    public const FORM_PARAMS = 'form_params';

    /**
     * 请求头映射
     * GuzzleHttp: RequestOptions::HEADERS => ['Header' => 'value']
     * Workerman: 'headers' => ['Header' => 'value']
     */
    public const HEADERS = 'headers';

    /**
     * 同步模式（在workerman中不需要，通过方法选择处理）
     * GuzzleHttp: RequestOptions::SYNCHRONOUS => true
     * Workerman: 忽略，通过调用同步方法处理
     */
    public const SYNCHRONOUS = 'synchronous';

    /**
     * JSON请求体映射
     * GuzzleHttp: RequestOptions::JSON => ['key' => 'value']
     * Workerman: 'data' => json_encode(['key' => 'value'])
     */
    public const JSON = 'json';

    /**
     * 请求体映射
     * GuzzleHttp: RequestOptions::BODY => 'content'
     * Workerman: 'data' => 'content'
     */
    public const BODY = 'body';

    /**
     * 超时时间映射
     * GuzzleHttp: RequestOptions::TIMEOUT => 30
     * Workerman: 在Client构造函数中设置
     */
    public const TIMEOUT = 'timeout';

    /**
     * 连接超时映射
     * GuzzleHttp: RequestOptions::CONNECT_TIMEOUT => 30
     * Workerman: 在Client构造函数中设置
     */
    public const CONNECT_TIMEOUT = 'connect_timeout';

    /**
     * 验证选项映射（SSL验证）
     * GuzzleHttp: RequestOptions::VERIFY => true/false
     * Workerman: 在Client构造函数中设置
     */
    public const VERIFY = 'verify';

    /**
     * 将GuzzleHttp格式的选项转换为workerman/http-client格式
     *
     * @param array $guzzleOptions GuzzleHttp格式的选项
     * @param string $uri 请求URI
     * @return array workerman/http-client格式的选项
     */
    public static function convertToWorkermanOptions(array $guzzleOptions, string $uri): array
    {
        $workermanOptions = [];

        if (isset($guzzleOptions[self::QUERY]) && is_array($guzzleOptions[self::QUERY])) {
            $uri = self::appendQueryParams($uri, $guzzleOptions[self::QUERY]);
        }

        if (isset($guzzleOptions[self::FORM_PARAMS]) && is_array($guzzleOptions[self::FORM_PARAMS])) {
            $workermanOptions['data'] = $guzzleOptions[self::FORM_PARAMS];
        }

        if (isset($guzzleOptions[self::JSON])) {
            $workermanOptions['data'] = json_encode($guzzleOptions[self::JSON]);
            if (!isset($workermanOptions['headers'])) {
                $workermanOptions['headers'] = [];
            }
            if (!isset($guzzleOptions[self::HEADERS]['Content-Type'])) {
                $workermanOptions['headers']['Content-Type'] = 'application/json';
            }
        }

        if (isset($guzzleOptions[self::BODY])) {
            $workermanOptions['data'] = $guzzleOptions[self::BODY];
        }

        if (isset($guzzleOptions[self::HEADERS]) && is_array($guzzleOptions[self::HEADERS])) {
            $workermanOptions['headers'] = array_merge(
                $workermanOptions['headers'] ?? [],
                $guzzleOptions[self::HEADERS]
            );
        }

        if (isset($guzzleOptions[self::TIMEOUT])) {
            $workermanOptions['timeout'] = $guzzleOptions[self::TIMEOUT];
        }

        if (isset($guzzleOptions[self::CONNECT_TIMEOUT])) {
            $workermanOptions['connect_timeout'] = $guzzleOptions[self::CONNECT_TIMEOUT];
        }

        if (isset($guzzleOptions[self::VERIFY])) {
            $workermanOptions['verify'] = $guzzleOptions[self::VERIFY];
        }

        return [$uri, $workermanOptions];
    }

    /**
     * 将查询参数附加到URI
     *
     * @param string $uri 原始URI
     * @param array $queryParams 查询参数
     * @return string 附加查询参数后的URI
     */
    private static function appendQueryParams(string $uri, array $queryParams): string
    {
        if (empty($queryParams)) {
            return $uri;
        }

        $queryString = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);

        if (strpos($uri, '?') === false) {
            return $uri . '?' . $queryString;
        }

        return $uri . '&' . $queryString;
    }
}
