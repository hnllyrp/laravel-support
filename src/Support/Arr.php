<?php

namespace Hnllyrp\LaravelSupport\Support;


class Arr extends \Illuminate\Support\Arr
{

    /**
     * 判断数组是否为多维数组
     */
    public static function isMultipleArray(array &$arr): bool
    {
        return collect($arr)->every(function ($item) {
            return is_array($item) && $item;
        });
    }
}
