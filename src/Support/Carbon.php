<?php

namespace Hnllyrp\LaravelSupport\Support;

use Illuminate\Support\Carbon as BaseCarbon;

class Carbon extends BaseCarbon
{
    public function __construct($time = null, $tz = null)
    {
        parent::__construct($time, $tz);

        if ($tz === null) {
            // 设置本地时区,北京时间差8小时问题
            $this->setTimezone(config('app.timezone', 'Asia/Shanghai'));
            // 设置中文语言包
            $this->setLocale(config('app.locale', 'zh_CN'));
        }
    }

}
