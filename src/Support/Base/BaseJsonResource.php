<?php

namespace Hnllyrp\LaravelSupport\Support\Base;

use Illuminate\Http\Resources\Json\JsonResource;

class BaseJsonResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @var array
     */
    protected $withoutFields = [];

    protected $hide = true;

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable
     */
    public function toArray($request)
    {
        return $this->filterFields(parent::toArray($request));
    }

    /**
     * @var BaseResourceCollection
     */
    public $resourceCollection;

    /**
     * 资源集合
     * @return static
     */
    public function resourceCollection()
    {
        $this->resourceCollection = tap(new BaseResourceCollection($this->resource), function ($collection) {
            $collection->collects = __CLASS__;
        });
        return $this;
    }

    /**
     * 隐藏指定字段
     *
     * @param array $fields
     * @return $this
     */
    public function except(array $fields)
    {
        $this->withoutFields = $fields;
        return $this;
    }

    /**
     * 显示指定字段
     *
     * @param array $fields
     * @return $this
     */
    public function only(array $fields)
    {
        $this->withoutFields = $fields;
        $this->hide = false;
        return $this;
    }

    /**
     * 删除过滤的键
     *
     * @param $array
     * @return array
     */
    protected function filterFields($array)
    {
        if (!$this->hide) {
            return collect($array)->only($this->withoutFields)->toArray();
        }

        return collect($array)->except($this->withoutFields)->toArray();
    }


}
