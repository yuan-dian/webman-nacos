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

namespace yuandian\WebmanNacos\Provider\V1;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;
use JetBrains\PhpStorm\ArrayShape;
use Psr\Http\Message\ResponseInterface;
use support\Log;
use yuandian\WebmanNacos\AbstractProvider;

class ConfigProvider extends AbstractProvider
{
    public const WORD_SEPARATOR = "\x02";

    public const LINE_SEPARATOR = "\x01";

    public function get(string $dataId, string $group, ?string $tenant = null): ResponseInterface
    {
        return $this->request('GET', 'nacos/v1/cs/configs', [
            RequestOptions::QUERY => $this->filter([
                'dataId' => $dataId,
                'group'  => $group,
                'tenant' => $tenant,
            ]),
        ]);
    }

    public function set(
        string $dataId,
        string $group,
        string $content,
        ?string $type = null,
        ?string $tenant = null
    ): ResponseInterface {
        return $this->request('POST', 'nacos/v1/cs/configs', [
            RequestOptions::FORM_PARAMS => $this->filter([
                'dataId'  => $dataId,
                'group'   => $group,
                'tenant'  => $tenant,
                'type'    => $type,
                'content' => $content,
            ]),
        ]);
    }

    public function delete(string $dataId, string $group, ?string $tenant = null): ResponseInterface
    {
        return $this->request('DELETE', 'nacos/v1/cs/configs', [
            RequestOptions::QUERY => $this->filter([
                'dataId' => $dataId,
                'group'  => $group,
                'tenant' => $tenant,
            ]),
        ]);
    }

    public function listener(
        #[ArrayShape([
            'dataId'     => 'string',
            'group'      => 'string',
            'contentMD5' => 'string',
            'tenant'     => 'string',
            'configId'   => 'string',
            'success'    => 'callable',
            'error'      => 'callable',
        ])]
        array $options = []
    ): PromiseInterface {
        $config = ($options['dataId'] ?? null) . self::WORD_SEPARATOR
            . ($options['group'] ?? null) . self::WORD_SEPARATOR
            . ($options['contentMD5'] ?? null) . self::WORD_SEPARATOR
            . ($options['tenant'] ?? null) . self::LINE_SEPARATOR;
        return $this->requestAsync('POST', 'nacos/v1/cs/configs/listener', [
            RequestOptions::QUERY   => [
                'Listening-Configs' => $config,
            ],
            RequestOptions::HEADERS => [
                'Long-Pulling-Timeout' => 30,
            ],
            RequestOptions::HEADERS

        ])->then(function ($response) use ($options) {
            if ($response->getStatusCode() === 200) {
                if (!empty((string)$response->getBody())) {
                    if (is_callable($options['success'])) {
                        $args = $options;
                        unset($args['success']);
                        unset($args['error']);
                        call_user_func($options['success'], $args);
                    }
                    Log::info("配置变更：" . (string)$response->getBody());
                }
            }
        }, function ($response) use ($options) {
            Log::error("长轮询更新配置失败：" . (string)$response);
            if (is_callable($options['error'])) {
                call_user_func($options['error'], $options);
            }
        });
    }

}