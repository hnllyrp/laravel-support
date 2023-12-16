<?php

namespace Hnllyrp\LaravelSupport\Support\Traits;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Arr;

/**
 * Trait HasBatch
 * laravel 操作 批量更新或插入
 *
 * @method static updateBatch(array $values, $uniqueBy, $update = null, $batchSize = 500)
 */
trait HasBatch
{
    /**
     * 参考：
     * https://github.com/mavinoo/laravelBatch/blob/master/src/Traits/HasBatch.php
     * https://github.com/yadakhov/insert-on-duplicate-key/blob/master/src/InsertOnDuplicateKey.php
     * laravel 8 upsert 方法
     */

    /**
     * 批量更新或插入
     * - 大数据量时，使用此方法可提高速度与性能
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $values
     * @param $uniqueBy
     * @param null $update
     * @param int $batchSize insert 500 (default), 100 minimum rows in one query
     * @return int|string
     */
    public function scopeUpdateBatch($query, array $values, $uniqueBy, $update = null, int $batchSize = 500)
    {
        if (method_exists(static::class, 'upsert')) {
            // laravel 8.x 开始可以使用 upsert 方法
            return static::upsert($values, $uniqueBy, $update);
        }

        $databaseQuery = $query->toBase();

        if (empty($values)) {
            return 0;
        } elseif ($update === []) {
            return (int)$databaseQuery->insert($values);
        }

        if (!is_array(reset($values))) {
            $values = [$values];
        } else {
            foreach ($values as $key => $value) {
                ksort($value);

                $values[$key] = $value;
            }
        }

        if (is_null($update)) {
            $update = array_keys(reset($values));
        }

        // 分批处理
        $minChunck = 100;
        $totalValues = count($values);
        $batchSizeInsert = ($totalValues < $batchSize && $batchSize < $minChunck) ? $minChunck : $batchSize;
        $totalChunk = ($batchSizeInsert < $minChunck) ? $minChunck : $batchSizeInsert;

        $values = array_chunk($values, $totalChunk, true);

        $count = 0;
        foreach ($values as $value) {
            $value = $this->addTimestampsToUpsertValues($value);
            $update = $this->addUpdatedAtToUpsertColumns($update);

            $sql = $this->compileUpsert($databaseQuery, $value, $uniqueBy, $update);
            $bindings = $this->cleanBindingsCustom(array_merge(
                Arr::flatten($value, 1),
                collect($update)->reject(function ($value, $key) {
                    return is_int($key);
                })->all()
            ));

            $count += $databaseQuery->getConnection()->affectingStatement($sql, $bindings);
        }

        return $count;
    }

    /**
     * Add timestamps to the inserted values.
     *
     * @param array $values
     * @return array
     */
    protected function addTimestampsToUpsertValues(array $values)
    {
        if (!$this->query()->getModel()->usesTimestamps()) {
            return $values;
        }

        $timestamp = $this->query()->getModel()->freshTimestampString();

        $columns = array_filter([
            $this->query()->getModel()->getCreatedAtColumn(),
            $this->query()->getModel()->getUpdatedAtColumn(),
        ]);

        foreach ($columns as $column) {
            foreach ($values as &$row) {
                $row = array_merge([$column => $timestamp], $row);
            }
        }

        return $values;
    }

    /**
     * Add the "updated at" column to the updated columns.
     *
     * @param array $update
     * @return array
     */
    protected function addUpdatedAtToUpsertColumns(array $update)
    {
        if (!$this->query()->getModel()->usesTimestamps()) {
            return $update;
        }

        $column = $this->query()->getModel()->getUpdatedAtColumn();

        if (!is_null($column) &&
            !array_key_exists($column, $update) &&
            !in_array($column, $update)) {
            $update[] = $column;
        }

        return $update;
    }

    /**
     * cleanBindingsCustom
     * Remove all of the expressions from a list of bindings.
     * @param array $bindings
     * @return array
     */
    protected function cleanBindingsCustom(array $bindings)
    {
        return array_values(array_filter($bindings, function ($binding) {
            return !$binding instanceof Expression;
        }));
    }

    public function compileUpsert(Builder $query, array $values, array $uniqueBy, array $update)
    {
        $useUpsertAlias = $query->connection->getConfig('use_upsert_alias');

        $grammar = $query->getGrammar();
        $sql = $grammar->compileInsert($query, $values);

        if ($useUpsertAlias) {
            $sql .= ' as laravel_upsert_alias';
        }

        $sql .= ' on duplicate key update ';

        $columns = collect($update)->map(function ($value, $key) use ($useUpsertAlias, $grammar) {
            if (!is_numeric($key)) {
                return $grammar->wrap($key) . ' = ' . $grammar->parameter($value);
            }

            return $useUpsertAlias
                ? $grammar->wrap($value) . ' = ' . $grammar->wrap('laravel_upsert_alias') . '.' . $grammar->wrap($value)
                : $grammar->wrap($value) . ' = values(' . $grammar->wrap($value) . ')';
        })->implode(', ');

        return $sql . $columns;
    }

}
