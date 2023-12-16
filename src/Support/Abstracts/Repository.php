<?php

namespace Hnllyrp\LaravelSupport\Support\Abstracts;

use BadMethodCallException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Class Repository 抽象类
 *
 * demo 示例
 *
 * new class TestRepository extends BaseRepository
 *
 * TestRepository::instance()->setModel('App\Models\Users')->where('id', 1)->first();
 * TestRepository::instance()->setTable('users')->where('id', 1)->first();
 *
 */
abstract class Repository
{
    /**
     * The base query builder instance.
     *
     * @var \Illuminate\Database\Query\Builder
     */
    protected $query;

    /**
     * The model being queried.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * The DB table name
     * @var string
     */
    protected $table;

    /**
     * The DB Query builder
     * @var \Illuminate\Database\Query\Builder
     */
    protected $db;

    public function __construct()
    {

    }

    /**
     * 静态方法调用
     *
     * @return static
     */
    public static function instance()
    {
        return app(static::class);
    }

    /**
     * Return the model instance.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Sets the name of the Eloquent model.
     *
     * @param string|null $model App\Models\Users
     * @return $this
     */
    public function setModel($model = null)
    {
        $this->model = $model;

        $this->query = $this->model::query();

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @param string $table
     * @return $this
     */
    public function setTable(string $table = '')
    {
        $this->table = $table;

        $this->db = DB::table($this->table);

        return $this;
    }

    /**
     * 扩展条件 类似 model::query()->where() 方法
     * @param $column
     * @param null $operator
     * @param null $value
     * @param string $boolean
     * @return $this
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and'): Repository
    {
        $this->query->where(...func_get_args());

        return $this;
    }

    /**
     * 扩展条件 类似 DB::table('users')->where() 方法
     * @param $column
     * @param null $operator
     * @param null $value
     * @param string $boolean
     * @return $this
     */
    public function whereDB($column, $operator = null, $value = null, $boolean = 'and'): Repository
    {
        $this->db->where(...func_get_args());

        return $this;
    }

    /**
     * insert
     * @param array $data
     * @return bool
     */
    public function insert(array $data = []): bool
    {
        if (empty($data)) {
            return false;
        }

        return $this->model->insert($data);
    }

    /**
     * insertGetId
     * @param array $data
     * @return int
     */
    public function insertGetId(array $data = []): int
    {
        if (empty($data)) {
            return 0;
        }

        return $this->model->insertGetId($data);
    }

    /**
     * delete
     * @param array $where
     * @return bool
     */
    public function delete(array $where = []): bool
    {
        if (empty($where)) {
            return false;
        }

        return $this->model->where($where)->delete();
    }

    /**
     * updateWhere
     *  $this->>repository()->updateWhere();
     * @param array $where
     * @param array $data
     * @return bool
     */
    public function updateWhere(array $where = [], array $data = []): bool
    {
        if (empty($where) || empty($data)) {
            return false;
        }

        $model = $this->model->query();

        if ($model->where($where)->exists()) {

            return $model->where($where)->update($data);
        }
        return false;
    }

    /**
     * updateOrInsert
     *  $this->>repository()->updateOrInsert();
     * @param array $where
     * @param array $data
     * @return bool
     */
    public function updateOrInsert(array $where = [], array $data = []): bool
    {
        if (empty($where) || empty($data)) {
            return false;
        }

        $model = $this->model->query();

        if (!$model->where($where)->exists()) {
            if ($this->model->usesTimestamps()) {
                $data[$this->model->getCreatedAtColumn()] = now()->timestamp;
            }
            return $model->insert(array_merge($where, $data));
        }

        return $model->where($where)->update($data);
    }

    /**
     * 扩展 whereIn 方法(Models) 返回以字段为键值数组的数据
     *
     * @param string $field 字段名称 exp: goods_id
     * @param array $values 字段值 exp: [1,2,3]
     * @param array $columns 查询列
     * @return array
     * @example
     *  AbstractsRepository extends Repository
     *     AbstractsRepository::instance()->setModel('App\Models\UserRank')->where('rank_id', 1)->whereInExtend('rank_id', [1,2], ['rank_name']);
     */
    public function whereInExtend(string $field = '', array $values = [], array $columns = ['*']): array
    {
        if (empty($field) || empty($values)) {
            return [];
        }

        $model = $this->query->whereIn($field, $values)->select($columns)->addSelect($field);

        $list = $model->get();

        // 返回 以 $field 字段名称 为键值数组
        return collect($list)->mapWithKeys(function ($item) use ($field) {
            return [$item[$field] => $item];
        })->toArray();
    }

    /**
     * 扩展whereIn方法(DB) 返回以字段为键值数组 数据
     * @param string $field 字段名称 exp: goods_id
     * @param array $values 字段值 exp: [1,2,3]
     * @param array $columns 查询字段
     * @return array
     * @example
     *   AbstractsRepository extends Repository
     *     AbstractsRepository::instance()->setTable('users')->whereDB('user_id', 1)->whereInExtendDB('user_id', [1,2], ['user_name']);
     */
    public function whereInExtendDB(string $field = '', array $values = [], array $columns = ['*']): array
    {
        if (empty($field) || empty($values)) {
            return [];
        }

        $builder = $this->db->whereIn($field, $values)->select($columns)->addSelect($field);

        $list = $builder->get();

        // 返回 以 $field 字段名称 为键值数组
        return collect($list)->mapWithKeys(function ($item) use ($field) {
            $item = (array)$item;
            return [$item[$field] => $item];
        })->toArray();
    }

    /**
     * 获取有效的新增和修改字段信息
     *
     * @param array $data 新增或者修改的数据
     * @param array|null $columns 表中的字段
     * @param string|null $primary 表的主键信息
     *
     * @return array
     */
    public function getValidColumns(array $data, array $columns = null, string $primary = null): array
    {
        $columns = $columns ?: $this->model->getFillable();
        $primary = $primary ?: $this->model->getKeyName();

        // 不管是新增还是修改、不允许操作主键字段
        unset($columns[$primary]);

        return Arr::only($data, $columns);
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
        if (method_exists(static::class, 'instance')) {
            return self::instance()->$method(...$parameters);
        }

        return (new static)->$method(...$parameters);
    }
}
