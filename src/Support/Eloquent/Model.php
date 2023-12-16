<?php

namespace Hnllyrp\LaravelSupport\Support\Eloquent;

use App\Kernel\Package\Spatie\Activitylog\Traits\LogsActivity;
use Hnllyrp\LaravelQueryCache\Traits\Cacheable;
use Hnllyrp\LaravelSupport\Support\Traits\Filterable;
use Hnllyrp\LaravelSupport\Support\Traits\HasBatch;
use Illuminate\Database\Eloquent\Model as EloquentModel;

/**
 * 公共 Model 基类
 * @method static findInSet($query, $column, $value)
 * @method static whereLike($query, $column, $value)
 */
abstract class Model extends EloquentModel
{
    /**
     * Cacheable 使用缓存
     * Model::cache(now()->addDay())->count();
     * Model::cacheForever('cache_key')->count();
     * Model::cacheRefresh('cache_key', 60)->count();
     *
     * Filterable model Filter
     * model::filter($filters)->get();
     *
     * HasBatch model 批量更新或插入
     * model::updateBatch($values, ['id'], ['value']);
     */
    use HasBatch, Filterable, Cacheable;

    /**
     * 使用记录操作日志。默认开启的，在需要记录日志的地方动态开启或关闭
     * demo:
     * $model = User::create(['name' => 'John']);
     * $model->enableLogging();
     * $model->disableLogging();
     * 或者
     * activity()
     *  ->setLogType(0)
     *  ->withProperties(['customProperty' => 'customValue'])
     *  ->log('Look, I logged something');
     *
     */
    use LogsActivity;

    protected static $logFillable = true;
    protected static $ignoreChangedAttributes = ['create_at', 'update_at', 'create_time', 'update_time'];

    /**
     * 组合日志信息
     * @param string $eventName
     * @return string
     */
    public function getDescriptionForEvent(string $eventName): string
    {
        $log_info = trans('user_log.log_action.' . $eventName);

        $value = static::getTable() ?? '';
        $name = static::getKeyName() ?? 'id'; // 自增id键名
        $id = static::getQueueableId() ?? 0; // 自增id值
        // exp: table id=1
        $content = $id ? '(' . $name . '=' . $id . ')' : '';

        if (!empty($value)) {
            $value = trans('user_log')['log_content'][$value] ?? $value; // 优先取语言包
            $log_info .= ': ' . addslashes($value) . $content;
        }
        return $log_info;
    }

    // 只记录更新后实际更改的属性
    protected static $logOnlyDirty = true;

    /**
     * 提供一个MySQL支持的find_in_set()查询构建器
     *
     * @param $query
     * @param $column
     * @param $value
     * @return mixed
     * @example model->findInSet('field', '11');
     */
    public function scopeFindInSet($query, $column, $value)
    {
        return $query->whereRaw("FIND_IN_SET(?, $column)", $value);
    }

    /**
     * WHERE $column LIKE %$value% query.
     *
     * @param  $query
     * @param  $column
     * @param  $value
     * @param string $boolean
     * @return mixed
     */
    public function scopeWhereLike($query, $column, $value, $boolean = 'and')
    {
        return $query->where($column, 'LIKE', "%$value%", $boolean);
    }

    /**
     * WHERE $column LIKE $value% query.
     *
     * @param  $query
     * @param  $column
     * @param  $value
     * @param string $boolean
     * @return mixed
     */
    public function scopeWhereBeginsWith($query, $column, $value, $boolean = 'and')
    {
        return $query->where($column, 'LIKE', "$value%", $boolean);
    }

    /**
     * WHERE $column LIKE %$value query.
     *
     * @param  $query
     * @param  $column
     * @param  $value
     * @param string $boolean
     * @return mixed
     */
    public function scopeWhereEndsWith($query, $column, $value, $boolean = 'and')
    {
        return $query->where($column, 'LIKE', "%$value", $boolean);
    }

    /**
     * 手动触发模型事件，适用于自定义方法
     * @param string $event
     * @param bool $halt
     */
    public function modelEvent($event, $halt = true)
    {
        $this->fireModelEvent($event, $halt);
    }

    /**
     * @param string $field
     * @param array $values
     * @param array|string[] $columns
     * @return array
     */
    public function whereInExtend(string $field = '', array $values = [], array $columns = ['*'])
    {
        if (empty($field) || empty($values)) {
            return [];
        }

        $query = static::query()->whereIn($field, $values)->select($columns)->addSelect($field);

        $list = $query->get();

        // 返回 以 $field 字段名称 为键值数组
        return collect($list)->mapWithKeys(function ($item) use ($field) {
            return [$item[$field] => $item];
        })->toArray();
    }
}
