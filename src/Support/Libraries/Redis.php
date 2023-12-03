<?php


namespace Hnllyrp\LaravelSupport\Support\Libraries;

use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Class Redis
 * @package App\Libraries
 */
class Redis
{
    private static $instance = null;

    private static $redis;

    protected $options = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => '',
        'select' => 1,
        'timeout' => 0,
        'expire' => 0,
        'persistent' => false,
        'prefix' => '',
    ];

    //防止直接创建对象
    private function __construct()
    {
        if (!extension_loaded('redis')) {
            throw new \BadFunctionCallException('not support: redis');
        }

        self::$redis = new \Redis();

        // 获取 redis 配置
        $config = config('think.redis');

        $this->options = array_merge($this->options, $config);

        //连接redis
        if ($this->options['persistent']) {
            self::$redis->pconnect($this->options['host'], $this->options['port'], $this->options['timeout'], 'persistent_id_' . $this->options['select']);
        } else {
            self::$redis->connect($this->options['host'], $this->options['port'], $this->options['timeout']);
        }

        if ($this->options['password']) {
            self::$redis->auth($this->options['password']);
        }
        if ($this->options['select']) {
            self::$redis->select($this->options['select']);
        }
    }

    //防止克隆对象
    private function __clone()
    {
    }

    //单一获取对象入口
    public static function getRedis()
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self;
        }
        return self::$redis;
    }

    /**
     * 是否连接正常
     * @param false $is_interrupt 是否抛异常
     * @return bool
     * @throws Exception
     */
    public static function hasRedis($is_interrupt = false)
    {
        $error_msg = '';
        try {
            $redis = self::getRedis();
            // 检测连接是否正常
            $redis->ping();
        } catch (\BadFunctionCallException $e) {
            // 缺少扩展
            $error_msg = $e->getMessage() ? $e->getMessage() : "缺少 redis 扩展";
        } catch (\RedisException $e) {
            // 连接拒绝
            Log::error('redis connection redisException fail: ' . $e->getMessage());
            $error_msg = $e->getMessage() ? $e->getMessage() : "redis 连接失败";
        } catch (Exception $e) {
            // 异常
            Log::error('redis connection fail: ' . $e->getMessage());
            $error_msg = $e->getMessage() ? $e->getMessage() : "redis 连接异常";
        }

        if ($error_msg) {
            if ($is_interrupt) {
                throw new Exception($error_msg);
            } else {
                return false;
            }
        }

        return true;
    }

}
