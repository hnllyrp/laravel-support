<?php

namespace Hnllyrp\LaravelSupport\Support\Base;

use BadMethodCallException;

class BaseService
{
    protected static $instance;

    protected $repository;

    public function __construct(
        BaseRepository $repository
    )
    {
        $this->repository = $repository;
    }

    /**
     * index
     */
    public function index()
    {
        return $this->repository->modelQuery()->get();
    }

    /**
     * Display a listing
     *
     * @param array $filters
     * @param int $page
     * @param int $limit
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function list(array $filters = [], int $page = 1, int $limit = 15)
    {
        if (!empty($filters['search'])) {
            $list = $this->repository->filter($filters, BaseFilter::class)->get();
        } else {
            $list = $this->repository->filter($filters, BaseFilter::class)->paginateFilter($limit);
        }

        return $list;
    }

    /**
     * create
     */
    public function create($data)
    {
        return $data;
    }

    /**
     * show
     */
    public function show(int $id = 0, array $filters = [])
    {
        if (!empty($filters) && empty($id)) {
            return $this->repository->filter($filters, BaseFilter::class)->first();
        }

        return $this->repository->show($id);
    }

    /**
     * store
     */
    public function store($data)
    {
        return $this->repository->create($data);
    }

    /**
     * edit
     */
    public function edit($id)
    {
        return $this->repository->show($id)->toArray();
    }

    /**
     * update
     */
    public function update(int $id = 0, $data = [])
    {
        return $this->repository->update($id, $data);
    }

    /**
     * batch delete 批量删除
     */
    public function destroy($id)
    {
        if (empty($id)) {
            return false;
        }

        if (is_array($id)) {
            return $this->repository->destroy($id);
        }

        return $this->repository->destroy($id);
    }

    /**
     * 简单实现：借用 app 容器，实现通过静态实例调用非静态方法，缺点是不能往构造传统非对象参数。
     * @return static
     */
    public static function instance()
    {
        if (is_null(static::$instance)) {
            static::$instance = app(static::class);
        }

        return static::$instance;
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
            return static::instance()->$method(...$parameters);
        }

        return (new static)->$method(...$parameters);
    }
}
