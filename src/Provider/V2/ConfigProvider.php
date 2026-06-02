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

namespace yuandian\WebmanNacos\Provider\V2;

use JetBrains\PhpStorm\ArrayShape;
use Workerman\Http\Response;
use yuandian\WebmanNacos\AbstractProvider;

class ConfigProvider extends AbstractProvider
{
    public const WORD_SEPARATOR = "\x02";

    public const LINE_SEPARATOR = "\x01";

    public function get(string $dataId, string $group, ?string $tenant = null): Response
    {
        return $this->request('GET', 'nacos/v2/cs/config', [
            'query' => $this->filter([
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
    ): Response {
        return $this->request('POST', 'nacos/v2/cs/config', [
            'form_params' => $this->filter([
                'dataId'  => $dataId,
                'group'   => $group,
                'tenant'  => $tenant,
                'type'    => $type,
                'content' => $content,
            ]),
        ]);
    }

    public function delete(string $dataId, string $group, ?string $tenant = null): Response
    {
        return $this->request('DELETE', 'nacos/v2/cs/config', [
            'query' => $this->filter([
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
            'contentMD5' => 'string', // md5(file_get_contents($configPath))
            'tenant'     => 'string',
        ])]
        array $options = []
    ): Response {
        $config = ($options['dataId'] ?? null) . self::WORD_SEPARATOR
            . ($options['group'] ?? null) . self::WORD_SEPARATOR
            . ($options['contentMD5'] ?? null) . self::WORD_SEPARATOR
            . ($options['tenant'] ?? null) . self::LINE_SEPARATOR;
        return $this->request('POST', 'nacos/v2/cs/config/listener', [
            'query'   => [
                'Listening-Configs' => $config,
            ],
            'headers' => [
                'Long-Pulling-Timeout' => 30000,
            ],
        ]);
    }

    public function listenerAsync(
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
    ): void {
        $config = ($options['dataId'] ?? null) . self::WORD_SEPARATOR
            . ($options['group'] ?? null) . self::WORD_SEPARATOR
            . ($options['contentMD5'] ?? null) . self::WORD_SEPARATOR
            . ($options['tenant'] ?? null) . self::LINE_SEPARATOR;
        $this->requestAsync('POST', 'nacos/v2/cs/config/listener', [
            'query'   => ['Listening-Configs' => $config],
            'headers' => ['Long-Pulling-Timeout' => 30000],
            'success' => function ($response) use ($options) {
                if ($response->getStatusCode() === 200 && !empty((string)$response->getBody())) {
                    if (is_callable($options['success'])) {
                        $args = $options;
                        unset($args['success'], $args['error']);
                        call_user_func($options['success'], $args);
                    }
                    \support\Log::info("配置变更：" . (string)$response->getBody());
                }
            },
            'error'   => function ($exception) use ($options) {
                \support\Log::error("长轮询更新配置失败：" . $exception);
                if (is_callable($options['error'])) {
                    call_user_func($options['error'], $options);
                }
            },
        ]);
    }
}
