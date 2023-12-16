<?php


namespace Hnllyrp\LaravelSupport\Support\Macros\Builder;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\DB;

/**
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @mixin \Illuminate\Database\Query\Builder
 */
class QueryBuilderMacro
{
    public function getToArray(): \Closure
    {
        return function ($columns = ['*']) {
            return $this->getModel()->get($columns)->toArray();
        };
    }

    public function firstToArray(): \Closure
    {
        return function ($columns = ['*']) {
            // return optional($this->first($columns))->toArray();
            return ($model = $this->first($columns)) ? $model->toArray() : (array)$model;
        };
    }

    public function whereLike(): callable
    {
        return function ($column, string $value, string $boolean = 'and', bool $not = false) {
            $operator = $not ? 'not like' : 'like';

            return $this->where($column, $operator, "%$value%", $boolean);
        };
    }

    /**
     * 查询前字段排除功能
     * User::withoutField('status','name')->get();
     *
     * @return \Closure
     */
    public function withoutField(): \Closure
    {
        return $this->selectNotField();
    }

    public function selectNotField(): \Closure
    {
        return function ($columns = ['*']) {

            $columns = is_array($columns) ? $columns : func_get_args();

            $filed = array_diff($this->newQuery()->getModel()->getFillable(), $columns);

            return $this->select($filed);
        };
    }

    public function whereNotLike(): \Closure
    {
        return function ($column, string $value) {
            return $this->whereLike($column, $value, 'and', true);
        };
    }

    public function orWhereLike(): callable
    {
        return function ($column, string $value) {
            return $this->whereLike($column, $value, 'or');
        };
    }

    public function orWhereNotLike(): callable
    {
        return function ($column, string $value) {
            return $this->whereLike($column, $value, 'or', true);
        };
    }

    public function whereFindInSet(): callable
    {
        // @var string|Arrayable|string[] $values
        return function (string $column, $values, string $boolean = 'and', bool $not = false) {
            if (str_contains($column, '.') && ($tablePrefix = DB::getTablePrefix()) && !str_starts_with($column, $tablePrefix)) {
                $column = $tablePrefix . $column;
            }

            $sql = $not ? "not find_in_set(?, $column)" : "find_in_set(?, $column)";

            $values instanceof Arrayable and $values = $values->toArray();
            \is_array($values) and $values = implode(',', $values);

            return $this->whereRaw($sql, $values, $boolean);
        };
    }

    public function whereNotFindInSet(): callable
    {
        // @var string|Arrayable|string[] $values
        return function (string $column, $values) {
            return $this->whereFindInSet($column, $values, 'and', true);
        };
    }

    public function orWhereFindInSet(): callable
    {
        // @var string|Arrayable|string[] $values
        return function (string $column, $values) {
            return $this->whereFindInSet($column, $values, 'or');
        };
    }

    public function orWhereNotFindInSet(): callable
    {
        // @var string|Arrayable|string[] $values
        return function (string $column, $values) {
            return $this->whereFindInSet($column, $values, 'or', true);
        };
    }

    public function whereFullText(): callable
    {
        /*
         * Add a "where fulltext" clause to the query.
         *
         * @param  string|string[]  $columns
         * @param  string  $value
         * @param  string  $boolean
         * @return $this
         */
        return function ($columns, $value, array $options = [], $boolean = 'and') {
            $type = 'Fulltext';

            $columns = (array)$columns;

            $this->wheres[] = compact('type', 'columns', 'value', 'options', 'boolean');

            $this->addBinding($value);

            return $this;
        };
    }

    public function orWhereFullText(): callable
    {
        /*
         * Add a "or where fulltext" clause to the query.
         *
         * @param  string|string[]  $columns
         * @param  string  $value
         * @return $this|callable
         */
        return function ($columns, $value, array $options = []) {
            return $this->whereFulltext($columns, $value, $options, 'or');
        };
    }

    /**
     * @see https://github.com/ankane/hightop-php
     */
    public function top(): callable
    {
        return function ($column, int $limit = null, ?bool $null = false, int $min = null, string $distinct = null) {
            if ($distinct === null) {
                $op = 'count(*)';
            } else {
                $quotedDistinct = $this->getGrammar()->wrap($distinct);
                $op = "count(distinct $quotedDistinct)";
            }

            $relation = $this->select($column)->selectRaw($op)->groupBy($column)->orderByRaw('1 desc')->orderBy($column);

            if ($limit !== null) {
                $relation = $relation->limit($limit);
            }

            if (!$null) {
                $relation = $relation->whereNotNull($column);
            }

            if ($min !== null) {
                $relation = $relation->havingRaw("$op >= ?", [$min]);
            }

            // can't use pluck with expressions in Postgres without an alias
            $rows = $relation->get()->toArray();
            $result = [];
            foreach ($rows as $row) {
                $values = array_values($row);

                /** @noinspection OffsetOperationsInspection */
                $result[$values[0]] = $values[1];
            }

            return $result;
        };
    }
}
