<?php

namespace Hnllyrp\LaravelSupport\Support\Base;

use Hnllyrp\LaravelSupport\Support\Abstracts\ModelFilter;

class BaseFilter extends ModelFilter
{
    /**
     * demo
     * @see packages/laravel-support/src/Support/Traits/Filterable.php
     *
     * model::filter($request)->get();
     *
     * $userFilter = Auth::user()->isAdmin() ? AdminFilter::class : BasicUserFilter::class;
     * model::filter($request, $userFilter)->get();
     *
     */

    // protected $blacklist = ['secretMethod']; // 黑名单方法

    /**
     * Array of filter that will be ignored
     * 过滤指定输入字段
     * @var array
     */
    protected static $except_filter = ['page', 'limit', '_token'];

    /**
     * 默认搜索 filter 方法，可继承重写
     * @param $search
     * @return BaseFilter
     */
    public function search($search)
    {
        return $this->where(function ($query) use ($search) {
            return $query;
                // $query->where('user_name', 'like', "%$search%")
                // ->orWhere('mobile', 'like', "%$search%")
                // ->orWhere('email', 'like', "%$search%");
        });
    }
}
