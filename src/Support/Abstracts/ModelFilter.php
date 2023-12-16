<?php

namespace Hnllyrp\LaravelSupport\Support\Abstracts;

use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Class ModelFilter
 * @link https://github.com/Tucker-Eric/EloquentFilter/blob/master/src/ModelFilter.php
 *
 * @mixin QueryBuilder
 */
abstract class ModelFilter
{
    /**
     * Array of method names that should not be called.
     *
     * @var array
     */
    protected $blacklist = [];

    /**
     * Array of filter that will be ignored
     * 过滤指定输入字段
     * @var array
     */
    protected static $except_filter = [];

    /**
     * Array of input to filter.
     *
     * @var array
     */
    protected $input;

    /**
     * @var QueryBuilder
     */
    protected $query;

    /**
     * Drop `_id` from the end of input keys when referencing methods.
     *
     * @var bool
     */
    protected $drop_id = true;

    /**
     * Convert input keys to camelCase
     * Ex: my_awesome_key will be converted to myAwesomeKey($value).
     *
     * @var bool
     */
    protected $camel_cased_methods = true;

    /**
     * ModelFilter constructor.
     *
     * @param  $query
     * @param array $input
     */
    public function __construct($query, array $input = [])
    {
        $this->query = $query;
        $this->input = $this->removeEmptyInput($input);
    }

    /**
     * @param  $method
     * @param  $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        $resp = call_user_func_array([$this->query, $method], $args);

        // Only return $this if query builder is returned
        // We don't want to make actions to the builder unreachable
        return $resp instanceof QueryBuilder ? $this : $resp;
    }

    /**
     * Remove empty strings from the input array.
     *
     * @param array $input
     * @return array
     */
    public function removeEmptyInput($input)
    {
        $filterableInput = [];

        // 排除指定输入字段
        if (!empty(static::$except_filter)) {
            $input = Arr::except($input, static::$except_filter);
        }

        foreach ($input as $key => $val) {
            if ($this->includeFilterInput($key, $val)) {
                $filterableInput[$key] = $val;
            }
        }

        return $filterableInput;
    }

    /**
     * Handle all filters.
     *
     * @return QueryBuilder
     */
    public function handle()
    {
        // Run input filters
        $this->filterInput();

        return $this->query;
    }

    /**
     * @param  $key
     * @return string
     */
    public function getFilterMethod($key)
    {
        // Remove '.' chars in methodName
        $methodName = str_replace('.', '', $this->drop_id ? preg_replace('/^(.*)_id$/', '$1', $key) : $key);

        // Convert key to camelCase?  userName
        return $this->camel_cased_methods ? Str::camel($methodName) : $methodName;
    }

    /**
     * Filter with input array.
     */
    public function filterInput()
    {
        foreach ($this->input as $key => $val) {
            // Call all local methods on filter
            $method = $this->getFilterMethod($key);

            if ($this->methodIsCallable($method)) {
                $this->{$method}($val);
            }
        }
    }

    /**
     * Retrieve input by key or all input as array.
     *
     * @param string|null $key
     * @param mixed|null $default
     * @return array|mixed|null
     */
    public function input($key = null, $default = null)
    {
        if ($key === null) {
            return $this->input;
        }

        return array_key_exists($key, $this->input) ? $this->input[$key] : $default;
    }

    /**
     * Set to drop `_id` from input. Mainly for testing.
     *
     * @param null $bool
     * @return bool
     */
    public function dropIdSuffix($bool = null)
    {
        if ($bool === null) {
            return $this->drop_id;
        }

        return $this->drop_id = $bool;
    }

    /**
     * Convert input to camel_case. Mainly for testing.
     *
     * @param null $bool
     * @return bool
     */
    public function convertToCamelCasedMethods($bool = null)
    {
        if ($bool === null) {
            return $this->camel_cased_methods;
        }

        return $this->camel_cased_methods = $bool;
    }

    /**
     * Add method to the blacklist so disable calling it.
     *
     * @param string $method
     * @return $this
     */
    public function blacklistMethod($method)
    {
        $this->blacklist[] = $method;

        return $this;
    }

    /**
     * Remove a method from the blacklist.
     *
     * @param string $method
     * @return $this
     */
    public function whitelistMethod($method)
    {
        $this->blacklist = array_filter($this->blacklist, function ($name) use ($method) {
            return $name !== $method;
        });

        return $this;
    }

    /**
     * @param  $method
     * @return bool
     */
    public function methodIsBlacklisted($method)
    {
        return in_array($method, $this->blacklist, true);
    }

    /**
     * Check if the method is not blacklisted and callable on the extended class.
     *
     * @param  $method
     * @return bool
     */
    public function methodIsCallable($method)
    {
        return !$this->methodIsBlacklisted($method) && method_exists($this, $method) &&
            !method_exists(ModelFilter::class, $method);
    }

    /**
     * Method to determine if input should be passed to the filter
     * Returning false will exclude the input from being used in filter logic.
     *
     * @param mixed $value
     * @param string $key
     * @return bool
     */
    protected function includeFilterInput($key, $value)
    {
        return $value !== '' && $value !== null && !(is_array($value) && empty($value));
    }

}
