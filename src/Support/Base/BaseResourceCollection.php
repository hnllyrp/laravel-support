<?php

namespace Hnllyrp\LaravelSupport\Support\Base;

use Illuminate\Http\Resources\Json\ResourceCollection;

class BaseResourceCollection extends ResourceCollection
{
    /**
     * @var array
     */
    protected $withoutFields = [];

    protected $hide = true;

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return $this->filterCollection(parent::toArray($request));
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
     *  隐藏字段 处理集合
     *
     * @param $array
     * @return array
     */
    protected function filterCollection($array)
    {
        return $this->collection->map(function ($item) {
            if (!$this->hide) {
                return collect($item)->only($this->withoutFields)->all();
            }
            return collect($item)->except($this->withoutFields)->all();

        })->all();
    }

}
