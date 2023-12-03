<?php

namespace Hnllyrp\LaravelSupport\Support\Abstracts;

use BadMethodCallException;

/**
 * Class Service 抽象类
 */
abstract class Service
{
    protected static $instance;

    /**
     * 简单实现：借用 app 容器，实现通过静态实例调用非静态方法，缺点是不能往构造传统非对象参数。
     * @return static
     */
    public static function instance()
    {
        if (is_null(static::$instance)) {
            static::$instance = app(static::class);
        }

        return static::$instance;
    }

    /**
     * Handle dynamic method calls into the class.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (method_exists($this, $method)) {
            return $this->$method(...$parameters);
        }

        throw new BadMethodCallException(sprintf(
            'Method %s::%s does not exist.', static::class, $method
        ));
    }

    /**
     * Handle dynamic static method calls into the method.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return (new static)->$method(...$parameters);
    }
}
