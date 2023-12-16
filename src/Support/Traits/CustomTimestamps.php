<?php


namespace Hnllyrp\LaravelSupport\Support\Traits;

use Hnllyrp\LaravelSupport\Support\Carbon;

/**
 * Trait CustomTimestamps
 * 自定义格式化时间戳，当同时有 create_time  update_time 时使用
 */
trait CustomTimestamps
{
    /**
     * 访问属性 create_time
     * @param $value
     * @return string
     */
    public function getCreateTimeAttribute($value)
    {
        return isset($value) ? Carbon::createFromTimestamp($value)->format('Y-m-d H:i:s') : '';
    }

    /**
     * 访问属性 update_time
     * @param $value
     * @return string
     */
    public function getUpdateTimeAttribute($value)
    {
        return isset($value) ? Carbon::createFromTimestamp($value)->format('Y-m-d H:i:s') : '';
    }

    /**
     * 修改属性 create_time
     *
     * @param $value
     * @return void
     */
    public function setCreateTimeAttribute($value)
    {
        $this->attributes['create_time'] = Carbon::now()->timestamp;
    }

    /**
     * 修改属性 update_time
     *
     * @param $value
     * @return void
     */
    public function setUpdateTimeAttribute($value)
    {
        $this->attributes['update_time'] = Carbon::now()->timestamp;
    }
}
