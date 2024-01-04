<?php

namespace Hnllyrp\LaravelSupport\Services\Demo;

use App\Models\Users;
use App\Services\RedisService;
use Illuminate\Support\Carbon;

class UserCacheService
{

    /**
     * mysql-redis 优化缓存更新策略，实现缓存与数据库的双写一致
     *  1. 根据id查询, 先查询redis, 再查询数据库,写入redis
     */
    public function queryWithCache($id = 0)
    {
        // 1. 从redis查询缓存
        $redis = RedisService::getInstance();

        $key = 'cache:user:' . $id;
        $user = $redis->get($key);

        // 2. 判断redis是否存在
        if (!empty($user)) {
            // 3. redis存在，直接返回
            return $user;
        }
        // 4. redis不存在，根据id查询数据库
        $user = Users::where('id', $id)->first();

        if (empty($user)) {
            // 5. 数据库不存在，返回错误
            return [];
        }

        // 6. 数据库存在，写入redis
        $redis->setex($key, $user, 30);

        return $user;
    }

    /**
     * 缓存穿透
     * mysql-redis 优化缓存更新策略，实现缓存与数据库的双写一致
     *  1. 根据id查询, 先查询redis, 再查询数据库,写入redis
     */
    public function queryWithCacheThrough($id = 0)
    {
        // 1. 从redis查询缓存
        $redis = RedisService::getInstance();

        $key = 'cache:user:' . $id;

        $user = $redis->get($key);

        // 2. 判断redis是否存在
        if (!empty($user)) {
            // 3. redis存在，直接返回
            return $user;
        }

        /**
         * 缓存穿透 逻辑
         * t1. 判断redis缓存是否空值, 将空值返回
         * t2. mysql数据不存在，将空值写入redis缓存
         */
        if (!is_null($user)) {
            return null;
        }

        // 4. redis不存在，根据id查询数据库
        $user = Users::where('id', $id)->first();

        if (blank($user)) {
            // 缓存穿透 - t2 mysql数据不存在，将空值写入redis缓存
            $redis->setex($key, "", 100);
            // 5. 数据库不存在，返回错误
            return null;
        }

        // 6. 数据库存在，写入redis
        $redis->setex($key, $user, 60);

        return $user;
    }

    // 互斥锁
    public function queryWithCacheMutex($id = 0)
    {
        // 1. 从redis查询缓存
        $redis = RedisService::getInstance();

        $key = 'cache:user:' . $id;

        $user = $redis->get($key);

        // 2. 判断redis是否存在
        if (!empty($user)) {
            // 3. redis存在，直接返回
            return $user;
        }

        /**
         * 缓存穿透 逻辑
         * t1. 判断redis缓存是否空值, 将空值返回
         * t2. mysql 数据不存在，将空值写入redis缓存
         */
        if (!is_null($user)) {
            return null;
        }

        /**
         * 互斥锁 逻辑
         * m1. 创建互斥锁并获取
         * m2. 判断是否获取成功
         * m3. 如果失败，则休眠并重试执行查询
         * m4. 释放互斥锁
         */
        $lock_key = "lock:shop:" . $id;
        try {
            $is_lock = $redis->lockMutex($lock_key, 'user:lock', 30);
            if (!$is_lock) {
                sleep(1);
                return $this->queryWithCacheMutex($id);
            }

            // 4. redis不存在，根据id查询数据库
            $user = Users::where('id', $id)->first();
            if (blank($user)) {
                // 缓存穿透 - t2 mysql数据不存在，将空值写入redis缓存
                $redis->setex($key, "", 100);
                // 5. 数据库不存在，返回错误
                return null;
            }

            // 6. 数据库存在，写入redis
            $redis->setex($key, $user, 60);
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage(), $exception->getCode());
        } finally {
            // m4. 释放互斥锁
            $redis->unlockMutex($lock_key);
        }

        return $user;
    }

    // 逻辑过期
    public function queryWithLogicExpire($id = 0)
    {
        // 1. 从redis查询缓存
        $redis = RedisService::getInstance();

        $key = 'cache:user:' . $id;
        $user = $redis->get($key);
        // 2. 判断redis是否存在
        if (is_null($user)) {
            // 3. redis存在，直接返回
            return null;
        }

        /**
         * 逻辑过期
         * l1. 判断redis缓存是否存在,如果存在再判断是否过期
         * l2. redis缓存未过期 直接返回信息
         * l3. 已过期 缓存重建
         */
        $data = $redis->getDataFromRedis($user);

        $expire_time = $data->expire_time ?? 0;
        $now = Carbon::now()->timestamp;
        if ($expire_time > 0 && $expire_time > $now) {
            // redis缓存未过期 直接返回信息
            $user = $data->data ?? null;
            return $user;
        }

        // l3. 已过期 缓存重建
        /**
         * 缓存重建
         * 1. 获取互斥锁
         * 2. 判断是否获取锁成功
         * 3. 获取锁成功,开启独立线程,实现缓存重建
         * 4. 释放锁
         */

        $lock_key = "lock:shop:" . $id;
        $is_lock = $redis->lockMutex($lock_key, 'user:lock', 30);
        if ($is_lock) {
            try {
                // 获取锁成功,开启独立线程,实现缓存重建
                $user = Users::where('id', $id)->first();
                sleep(1);
                $redis->setDataToRedis($key, $user, 30);
            } catch (\Exception $exception){

            } finally {
                // 释放锁
                $redis->unlockMutex($lock_key);
            }
        }

        // 返回过期的信息
        $user = $data->data ?? null;
        return $user;
    }


    /**
     * mysql-redis 优化缓存更新策略，实现缓存与数据库的双写一致
     * 2. 根据id修改时，先修改数据库，再删除缓存
     */
    public function updateWithCache($id, array $array)
    {
        // 1. 更新数据库
        $bool = Users::where('id', $id)->update($array);
        if ($bool) {
            // 2. 删除缓存
            $redis = RedisService::getInstance();

            $key = 'cache:user:' . $id;
            $redis->client()->del($key);
        }

        return $bool;
    }


}
