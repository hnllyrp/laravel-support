<?php

namespace Hnllyrp\LaravelSupport\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Arr as BaseArr;

class Arr extends BaseArr
{

    /**
     * 转数组
     * 参考 src/Illuminate/Http/JsonResponse.php setData方法
     * @param mixed $data
     * @return mixed
     */
    public static function toArray($data)
    {
        if (is_array($data)) {
            return $data;
        }

        if ($data instanceof Jsonable) {
            $json = $data->toJson();
        } elseif ($data instanceof \JsonSerializable) {
            $json = \json_encode($data->jsonSerialize());
        } elseif ($data instanceof Arrayable) {
            $json = \json_encode($data->toArray());
        } else {
            $json = \json_encode($data);
        }

        return \json_decode($json, true);
    }

    /**
     * 判断数组是否为多维数组
     */
    public static function isMultiArray(array &$arr): bool
    {
        return collect($arr)->every(function ($item) {
            return is_array($item) && $item;
        });
    }

    /**
     * 将数组里的null值过滤掉
     * @param array $arr
     * @return array
     */
    public static function fill_null(array $arr)
    {
        return \Hnllyrp\PhpSupport\Arr::array_filter_null($arr);
    }

    /**
     * 合并多维数组
     * @param array $arr1
     * @param array $arr2
     * @return array
     */
    public static function array_merge_multi(array $arr1, array $arr2)
    {
        return \Hnllyrp\PhpSupport\Arr::array_merge_multi($arr1, $arr2);
    }
}
