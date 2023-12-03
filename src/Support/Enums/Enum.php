<?php

namespace Hnllyrp\LaravelSupport\Support\Enums;

use ReflectionClass;
use ReflectionException;

/**
 * Class Enum 枚举基类
 * https://segmentfault.com/a/1190000018836345
 */
abstract class Enum
{
    const __default = null;

    /**
     * @var string
     */
    protected static $value;
    /**
     * @var ReflectionClass
     */
    protected static $reflectionClass;

    // 注意这里 将构造函数的 修饰符改成了 受保护的 即 外部无法直接 new
    protected function __construct($value = null)
    {
        // 很常规
        self::$value = is_null($value) ? static::__default : $value;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        // 实例化一个反射类 static::class 表示调用者
        $reflectionClass = self::getReflectionClass();
        // 这里我们要有一个约定， 就是类常量成员的名字必须的大写。
        // 这里就是取出来调用的静态方法名对应的常量值 虽然这里有个 getValue 方法
        // 但是因为其返回值不可靠 我们就依赖于他原本的隐式的 __toString 方法来帮我们输出字符串即可。
        $constant = $reflectionClass->getConstant(strtoupper($name));
        // 获取调用者的 构造方法
        $construct = $reflectionClass->getConstructor();
        // 设置成可访问 因为我们把修饰符设置成了受保护的 这里需要访问到，所以就需要设置成可访问的。
        $construct->setAccessible(true);
        // 因为现在类已经是可以访问的了所以我们直接实例化即可，实例化之后 PHP 会自动调用 __toString 方法 使得返回预期的值。
        $static = new static($constant);
        return $static;
    }

    /**
     * 实例化一个反射类
     * @return ReflectionClass
     * @throws ReflectionException
     */
    protected static function getReflectionClass()
    {
        if (!self::$reflectionClass instanceof ReflectionClass) {
            self::$reflectionClass = new ReflectionClass(static::class);
        }
        return self::$reflectionClass;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)self::$value;
    }

    /**
     * 判断一个值是否有效 即是否为枚举成员的值
     * @param $val
     * @return bool
     * @throws ReflectionException
     */
    public static function isValid($val)
    {
        return in_array($val, self::toArray());
    }

    /**
     * 转换枚举成员为键值对输出
     * @return array
     * @throws ReflectionException
     */
    public static function toArray()
    {
        return self::getEnumMembers();
    }

    /**
     * 获取枚举的常量成员数组
     * @return array
     * @throws ReflectionException
     */
    public static function getEnumMembers()
    {
        return self::getReflectionClass()->getConstants();
    }

    /**
     * 获取枚举成员值数组
     * @return array
     * @throws ReflectionException
     */
    public static function values()
    {
        return array_values(self::toArray());
    }

    /**
     * 获取枚举成员键数组
     * @return array
     * @throws ReflectionException
     */
    public static function keys()
    {
        return array_keys(self::getEnumMembers());
    }

    /**
     * 判断 Key 是否有效 即存在
     * @param $key
     * @return bool
     * @throws ReflectionException
     */
    public static function isKey($key)
    {
        return in_array($key, array_keys(self::getEnumMembers()));
    }

    /**
     * 根据 Key 去获取枚举成员值
     * @param $key
     * @return static
     */
    public static function getKey($key)
    {
        return self::$key();
    }

    /**
     * 格式枚举结果类型
     * @param null|bool|int $type 当此处的值时什么类时 格式化输出的即为此类型
     * @return bool|int|string|null
     */
    public function format($type = null)
    {
        switch (true) {
            // 当为纯数字 或者类型处传入的为 int 值时 转为 int
            case ctype_digit(self::$value) || is_int($type):
                return (int)self::$value;
                break;
            // 当 type 传入 true 时 返回 bool 类型
            case $type === true:
                return (bool)filter_var(self::$value, FILTER_VALIDATE_BOOLEAN);
                break;
            default:
                return self::$value;
                break;
        }
    }

}
