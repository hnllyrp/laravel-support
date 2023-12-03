<?php

namespace Hnllyrp\LaravelSupport\Support\Eloquent;

use Hnllyrp\LaravelQueryCache\Traits\Cacheable;
use Hnllyrp\LaravelSupport\Support\Traits\Filterable;
use Illuminate\Database\Eloquent\Model as EloquentModel;

/**
 * 公共 Model 基类
 * @method static findInSet($query, $column, $value)
 * @method static filter($query, $input = [], $filter = null)
 * @method static whereLike($query, $column, $value)
 * @method static whereHasIn(string $relation, ?\Closure $callable = null)
 */
abstract class Model extends EloquentModel
{
    /**
     * 使用缓存
     * Model::cache(now()->addDay())->count();
     * Model::cacheForever('cache_key')->count();
     * Model::cacheRefresh('cache_key', 60)->count();
     */
    use Cacheable, Filterable;

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
     * @param string $field
     * @param array $values
     * @param array|string[] $columns
     * @return array
     */
    public static function whereInExtend(string $field = '', array $values = [], array $columns = ['*']): array
    {
        if (empty($field) || empty($values)) {
            return [];
        }

        $query = static::query()->whereIn($field, $values)->select($columns)->addSelect($field);

        $list = $query->get();

        $list = $list ? $list->toArray() : [];

        // 返回 以 $field 字段名称 为键值数组
        return collect($list)->mapWithKeys(function ($item) use ($field) {
            return [$item[$field] => $item];
        })->toArray();
    }
}
