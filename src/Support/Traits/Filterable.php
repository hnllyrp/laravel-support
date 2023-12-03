<?php


namespace Hnllyrp\LaravelSupport\Support\Traits;

/**
 * Trait Filterable
 * @link https://github.com/Tucker-Eric/EloquentFilter/blob/master/src/Filterable.php
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
     * demo  model::filter($filters)->get();
     *
     * @param  $query
     * @param array $input
     * @param null $filter
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilter($query, $input = [], $filter = null)
    {
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
     * Returns the ModelFilter for the current model.
     *
     * @return string
     */
    public function getModelFilterClass()
    {
        return method_exists($this, 'modelFilter') ? $this->modelFilter() : $this->provideFilter();
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
            $filter = config('kernel.filter.namespace', 'App\Filters') . '\\' .  class_basename($this) . 'Filter';
        }

        return $filter;
    }
}
