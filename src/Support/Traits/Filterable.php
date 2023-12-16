<?php

namespace Hnllyrp\LaravelSupport\Support\Traits;

use Illuminate\Support\Arr;

/**
 * Trait Filterable
 * @link https://github.com/Tucker-Eric/EloquentFilter/blob/master/src/Filterable.php
 *
 * @method static filter($query, array $input = [], $filter = null)
 * @method static paginateFilter($query, $perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
 * @method static simplePaginateFilter($query, $perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
 */
trait Filterable
{
    /**
     * Array of input used to filter the query.
     *
     * @var array
     */
    protected $filtered = [];

    /**
     * Creates local scope to run the filter.
     *
     * demo
     * model::filter($filters)->get();
     * $users = model::filter($request->all())->paginateFilter(15);
     * $users = model::filter($request->all())->simplePaginateFilter(15);
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $input
     * @param null $filter
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilter($query, array $input = [], $filter = null)
    {
        // Resolve the current Model's filter
        if ($filter === null) {
            $filter = $this->getModelFilterClass();
        }

        // Create the model filter instance
        $modelFilter = new $filter($query, $input);

        // Set the input that was used in the filter (this will exclude empty strings)
        $this->filtered = $modelFilter->input();

        // Return the filter query
        return $modelFilter->handle();
    }

    /**
     * Paginate the given query with url query params appended.
     *
     * @param int $perPage
     * @param array $columns
     * @param string $pageName
     * @param int|null $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     *
     * @throws \InvalidArgumentException
     */
    public function scopePaginateFilter($query, $perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $perPage = $perPage ?: 15;
        $paginator = $query->paginate($perPage, $columns, $pageName, $page);
        $paginator->appends($this->filtered);

        return $paginator;
    }

    /**
     * Paginate the given query with url query params appended.
     *
     * @param int $perPage
     * @param array $columns
     * @param string $pageName
     * @param int|null $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     *
     * @throws \InvalidArgumentException
     */
    public function scopeSimplePaginateFilter($query, $perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $perPage = $perPage ?: 15;
        $paginator = $query->simplePaginate($perPage, $columns, $pageName, $page);
        $paginator->appends($this->filtered);

        return $paginator;
    }

    /**
     * Returns ModelFilter class to be instantiated.
     *
     * @param null|string $filter
     * @return string
     */
    public function provideFilter($filter = null)
    {
        if ($filter === null) {
            $filter = config('kernel.filter.namespace', 'App\Filters') . '\\' . class_basename($this) . 'Filter';
        }

        return $filter;
    }

    /**
     * Returns the ModelFilter for the current model.
     *
     * @return string
     */
    public function getModelFilterClass()
    {
        return method_exists($this, 'modelFilter') ? $this->modelFilter() : $this->provideFilter();
    }
}
