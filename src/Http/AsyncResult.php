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

use Psr\Http\Message\ResponseInterface;

class AsyncResult
{
    private ?ResponseInterface $response = null;
    private ?\Throwable $exception = null;
    private array $onFulfilledCallbacks = [];
    private array $onRejectedCallbacks = [];
    private bool $isResolved = false;

    public function __construct(
        private readonly \Workerman\Http\Client $client,
        private readonly string $uri,
        private readonly array $options
    ) {}

    public function then(callable $onFulfilled, callable $onRejected): self
    {
        if ($this->isResolved) {
            if ($this->exception !== null) {
                $onRejected($this->exception);
            } else {
                $onFulfilled($this->response);
            }
            return $this;
        }

        $this->onFulfilledCallbacks[] = $onFulfilled;
        $this->onRejectedCallbacks[] = $onRejected;
        return $this;
    }

    public function wait(): ResponseInterface
    {
        if ($this->isResolved) {
            if ($this->exception !== null) {
                throw $this->exception;
            }
            return $this->response;
        }

        $workermanOptions = $this->options;
        $workermanOptions['success'] = function (ResponseInterface $response) {
            $this->resolve($response);
        };
        $workermanOptions['error'] = function (\Throwable $exception) {
            $this->reject($exception);
        };

        $this->client->request($this->uri, $workermanOptions);

        if ($this->exception !== null) {
            throw $this->exception;
        }

        return $this->response;
    }

    private function resolve(ResponseInterface $response): void
    {
        $this->response = $response;
        $this->isResolved = true;

        foreach ($this->onFulfilledCallbacks as $callback) {
            $callback($response);
        }

        $this->onFulfilledCallbacks = [];
        $this->onRejectedCallbacks = [];
    }

    private function reject(\Throwable $exception): void
    {
        $this->exception = $exception;
        $this->isResolved = true;

        foreach ($this->onRejectedCallbacks as $callback) {
            $callback($exception);
        }

        $this->onFulfilledCallbacks = [];
        $this->onRejectedCallbacks = [];
    }
}
