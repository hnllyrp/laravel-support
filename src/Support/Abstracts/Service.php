<?php

namespace Hnllyrp\LaravelSupport\Support\Abstracts;

/**
 * Class Service 抽象类
 */
abstract class Service
{
    /**
     * 静态方法调用
     *
     * @return static
     */
    public static function instance(): Service
    {
        return app(static::class);
    }
}
