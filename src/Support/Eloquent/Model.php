<?php

namespace Hnllyrp\LaravelSupport\Support\Eloquent;

use Illuminate\Database\Eloquent\Model as Base;

/**
 * 公共 Model 基类
 */
class Model extends Base
{
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
     * @param int $id
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Builder|Base|object|null
     */
    public static function getOneById($id = 0, $columns = [])
    {
        $model = static::query()->where(self::getKeyName()(), $id);

        if ($columns) {
            $model = $model->select($columns);
        }

        return $model->first();
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
