<?php

namespace Hnllyrp\LaravelSupport\Support\Base;

use App\Kernel\Support\CustomPaginate;
use BadMethodCallException;
use Closure;
use Hnllyrp\LaravelSupport\Support\Arr;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;

/**
 * Class BaseRepository 抽象类
 *
 * demo 示例
 *
 *  UserRepository::instance()->model()->first();
 *  UserRepository::instance()->find(1);
 *  UserRepository::query()->where('id', 1)->get();
 *
 * 或者
 * new class TestRepository extends BaseRepository
 *
 * TestRepository::instance()->setModel('App\Models\Users')->where('id', 1)->first();
 * TestRepository::instance()->setTable('users')->where('id', 1)->first();
 *
 */
abstract class BaseRepository
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
     * @var \Hnllyrp\LaravelSupport\Support\Eloquent\Model
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

    /**
     * BaseRepository constructor.
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;

        $this->query = $this->model::query();
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
     * Model
     * @return Model
     */
    public function getModel()
    {
        return $this->model;
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
        $this->table = $table ?: $this->model->getTable();

        $this->db = DB::table($this->table);

        return $this;
    }

    /**
     * Model query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function query()
    {
        return $this->model->query();
    }

    /**
     * Model
     * @return Model
     */
    public function model()
    {
        return $this->model;
    }

    /**
     * Model query
     * @return Builder
     */
    public function modelQuery(): Builder
    {
        return $this->model->query();
    }

    /**
     * @param $data
     * @return Builder|Model
     */
    public function create($data)
    {
        return $this->query()->firstOrCreate($data);
    }

    /**
     * @param int $id
     * @return Model
     */
    public function show(int $id)
    {
        return $this->query()->find($id);
    }

    /**
     *
     * @param int $id
     * @param  $data
     * @return bool
     */
    public function update(int $id = 0, $data = [])
    {
        return $this->query()->find($id)->update($data);
    }

    /**
     * delete 删除
     * @param int $id
     * @return bool|null
     * @throws \Exception
     */
    public function delete(int $id)
    {
        return $this->query()->find($id)->delete();
    }

    /**
     * batch delete 批量删除
     * @param $ids
     * @return int
     */
    public function destroy($ids)
    {
        return $this->model->destroy($ids);
    }

    /**
     * 普通分页 返回总数
     * @param $perPage
     * @param string[] $columns
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage, $columns = ['*'])
    {
        return $this->query->paginate($perPage, $columns);
    }

    /**
     * 倒序 普通游标分页. 从大到小查询 1000 -> 100 -> 10 -> 1
     *   应用场景：大数据量分页优化，一般用于不需要指定跳转至第几页的分页场景，特别适合前台滚动加载分页
     * @param int $perPage
     * @param string[] $columns
     * @param string $pageName
     * @param null $page
     * @param string $column 一般用主键 id
     * @return mixed|object|LengthAwarePaginator
     */
    public function cursorPaginateDesc($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null, $column = 'id')
    {
        // last_id 一般用主键最大值 首次查询由后端返回下一个主键id值，再由前端传入下一次分页请求接口
        $lastId = request()->input('last_id', 0);
        if (empty($lastId)) {
            // sql: select user_id from `users` order by `user_id` desc limit 1;
            $latest = $this->query->latest($column)->first($column);
            $lastId = $latest->$column ?? 0;
        }

        // 总数
        $total = $this->query->count();

        // sql: select * from `users` where `user_id` < 16 order by `user_id` desc limit 15
        $items = $this->query->forPageBeforeId($perPage, $lastId, $column)->get($columns);
        $lastIdNext = $items->last() ? $items->last()->$column : 0;

        $options = [
            'last_id' => $lastIdNext
        ];
        return CustomPaginate::paginate($items, $perPage, $total, $pageName, $page, $options);
    }

    /**
     * 正序 普通游标分页. 从小到大查询 1 -> 10 -> 100 -> 1000
     *  应用场景：大数据量分页优化，一般用于不需要指定跳转至第几页的分页场景，特别适合前台滚动加载分页
     * @param int $perPage
     * @param string[] $columns
     * @param string $pageName
     * @param null $page
     * @param string $column 一般用主键 id
     * @return mixed|object|LengthAwarePaginator
     */
    public function cursorPaginateAsc($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null, $column = 'id')
    {
        // first_id 一般用主键最小值 首次查询由后端返回下一个主键id值，再由前端传入下一次分页请求接口
        $firstId = request()->input('first_id', 0);
        if (empty($firstId)) {
            // sql: select user_id from `users` order by `user_id` asc limit 1;
            $oldest = $this->query->oldest($column)->first($column);
            $firstId = $oldest->$column ?? 0;
        }

        // 总数
        $total = $this->query->count();

        // sql: select * from `users` where `user_id` > 15 order by `user_id` asc limit 15
        $items = $this->query->forPageAfterId($perPage, $firstId - 1, $column)->get($columns);
        $firstIdNext = $items->last() ? $items->last()->$column : 0;

        $options = [
            'first_id' => $firstIdNext
        ];
        return CustomPaginate::paginate($items, $perPage, $total, $pageName, $page, $options);
    }

    /**
     * 倒序 简单游标分页. 从大到小查询 1000 -> 100 -> 10 -> 1
     *   应用场景：大数据量分页优化，一般用于不需要指定跳转至第几页的分页场景，不返回总数，特别适合前台滚动加载分页
     * @param int $perPage
     * @param string[] $columns
     * @param string $pageName
     * @param null $page
     * @param string $column 一般用主键 id
     * @return mixed|object|Paginator
     */
    public function cursorSimplePaginateDesc($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null, $column = 'id')
    {
        // last_id 一般用主键最大值 首次查询由后端返回下一个主键id值，再由前端传入下一次分页请求接口
        $lastId = request()->input('last_id', 0);
        if (empty($lastId)) {
            // sql: select user_id from `users` order by `user_id` desc limit 1;
            $latest = $this->query->latest($column)->first($column);
            $lastId = $latest->$column ?? 0;
        }

        // sql: select * from `users` where `user_id` < 16 order by `user_id` desc limit 15
        $items = $this->query->forPageBeforeId($perPage, $lastId, $column)->get($columns);
        $lastIdNext = $items->last() ? $items->last()->$column : 0;

        $options = [
            'last_id' => $lastIdNext
        ];
        return CustomPaginate::simplePaginate($items, $perPage, $pageName, $page, $options);
    }

    /**
     * 正序 简单游标分页. 从小到大查询 1 -> 10 -> 100 -> 1000
     *  应用场景：大数据量分页优化，一般用于不需要指定跳转至第几页的分页场景，不返回总数，特别适合前台滚动加载分页
     * @param int $perPage
     * @param string[] $columns
     * @param string $pageName
     * @param null $page
     * @param string $column 一般用主键 id
     * @return mixed|object|Paginator
     */
    public function cursorSimplePaginateAsc($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null, $column = 'id')
    {
        // first_id 一般用主键最小值 首次查询由后端返回下一个主键id值，再由前端传入下一次分页请求接口
        $firstId = request()->input('first_id', 0);
        if (empty($firstId)) {
            // sql: select user_id from `users` order by `user_id` asc limit 1;
            $oldest = $this->query->oldest($column)->first($column);
            $firstId = $oldest->$column ?? 0;
        }

        // sql: select * from `users` where `user_id` > 15 order by `user_id` asc limit 15
        $items = $this->query->forPageAfterId($perPage, $firstId - 1, $column)->get($columns);
        $firstIdNext = $items->last() ? $items->last()->$column : 0;

        $options = [
            'first_id' => $firstIdNext
        ];
        return CustomPaginate::simplePaginate($items, $perPage, $pageName, $page, $options);
    }

    /**
     * Model filter
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function filter($input = [], $filter = null)
    {
        return $this->model::filter($input, $filter);
    }

    /**
     * 批量更新或插入
     *
     * @param array $values
     * @param $uniqueBy
     * @param null $update
     * @param int $batchSize
     * @return mixed
     */
    public function updateBatch(array $values, $uniqueBy, $update = null, $batchSize = 500)
    {
        $result = $this->model::updateBatch($values, $uniqueBy, $update, $batchSize);
        if ($result) {
            if (method_exists($this->model, 'modelEvent')) {
                $this->model->modelEvent('updatedBatch', true);
            }
        }
        return $result;
    }

    /**
     * Add a basic where clause to the query.
     *
     * 扩展条件查询 同 model::query()->where() 方法使用
     * exp: $this->>repository()->where()->get();
     *
     * @param \Closure|string|array|\Illuminate\Database\Query\Expression $column
     * @param null $operator
     * @param null $value
     * @param string $boolean
     * @return $this
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        if ($column instanceof Closure) {
            $column($query = $this->model->newQueryWithoutRelationships());

            $this->query->addNestedWhereQuery($query->getQuery(), $boolean);
        } else {
            $this->query->where(...func_get_args());
        }

        return $this;
    }

    /**
     * Add an "or where" clause to the query.
     *
     *  同 model::query()->orWhere() 方法使用
     *  exp: $this->>repository()->orWhere()->get();
     *
     * @param \Closure|array|string|\Illuminate\Database\Query\Expression $column
     * @param mixed $operator
     * @param mixed $value
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        return $this->where($column, $operator, $value, 'or');
    }

    protected function prepareValueAndOperator($value, $operator, $useDefault = false)
    {
        if ($useDefault) {
            return [$operator, '='];
        }

        return [$value, $operator];
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
    public function updateOrInsert(array $where = [], array $data = [])
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
     * @example UsersRepository::instance()->where('id', 1)->whereInExtend('rank_id', [1,2], ['rank_name']);
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
     * 扩展条件 类似 DB::table('users')->where() 方法
     * @param $column
     * @param null $operator
     * @param null $value
     * @param string $boolean
     * @return $this
     */
    public function whereDB($column, $operator = null, $value = null, $boolean = 'and')
    {
        $this->db->where(...func_get_args());

        return $this;
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
            $item = Arr::toArray($item);
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
    public function getValidColumns(array $data, array $columns = null, string $primary = null)
    {
        $columns = $columns ?: $this->model->getFillable();
        $primary = $primary ?: $this->model->getKeyName();

        // 不管是新增还是修改、不允许操作主键字段
        unset($columns[$primary]);

        return Arr::only($data, $columns);
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
