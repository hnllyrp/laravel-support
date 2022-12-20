<?php

namespace Hnllyrp\LaravelSupport\Support;


use Illuminate\Support\Carbon as BaseCarbon;

class Carbon extends BaseCarbon
{
    /**
     * 本地时区时间,处理差8小时的问题
     * 例如 东八区
     * @return $this
     */
    public function localTime()
    {
        $this->setTimezone(config('app.timezone'));

        return $this;
    }
}
