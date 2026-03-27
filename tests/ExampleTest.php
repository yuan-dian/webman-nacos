<?php

declare(strict_types=1);

namespace yuandian\WebmanNacos\Tests;

class ExampleTest extends TestCase
{
    public function testTrueIsTrue(): void
    {
        self::assertTrue(true);
    }

    public function testFalseIsFalse(): void
    {
        self::assertFalse(false);
    }

    public function testOnePlusOneEqualsTwo(): void
    {
        self::assertSame(2, 1 + 1);
    }

    public function testStringEquality(): void
    {
        self::assertSame('webman-nacos', 'webman-nacos');
    }

    public function testArrayHasExpectedKeys(): void
    {
        $data = [
            'name' => 'webman-nacos',
            'version' => '1.0.0',
            'features' => ['config', 'service-discovery'],
        ];

        self::assertArrayHasKey('name', $data);
        self::assertArrayHasKey('version', $data);
        self::assertArrayHasKey('features', $data);
    }

    public function testStringContains(): void
    {
        $haystack = 'WebmanNacos is a Nacos client for Webman';
        $needle = 'Nacos';

        self::assertStringContainsString($needle, $haystack);
    }

    public function testStringStartsWith(): void
    {
        self::assertStringStartsWith('Webman', 'WebmanNacos');
    }

    public function testStringEndsWith(): void
    {
        self::assertStringEndsWith('Nacos', 'WebmanNacos');
    }
}