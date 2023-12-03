<?php

namespace Tests\Unit;

use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * Class CarbonTest
 * @example vendor/bin/phpunit tests/Unit/CarbonTest
 * @package Tests\Unit
 */
class CarbonTest extends TestCase
{
    /**
     * test carbon
     */
    public function testCarbon()
    {

        // 格式化时间为时间戳
        // $time = Carbon::parse('2012-10-5 23:26:11')->timestamp; // int(1349479571)
        // 时间戳转换为 字符串
        // $date_string = Carbon::parse(1349479571)->toDateString(); // Y-m-d

        // 当前时间戳
        $now = Carbon::now()->timestamp; // 169957925

        // 昨天时间戳
        // $yesterday = Carbon::yesterday()->timestamp;

        // 当前月份的开始与结束时间
        // $dt = Carbon::createFromTimestamp($time);
        // $dt = Carbon::now();
        // $subDay = $dt->subDay();
        // $subDay->startOfDay()->toDateTimeString(),$subDay->endOfDay()->toDateTimeString()
        // $addDay = $dt->addDay();

        // dd($addDay->endOfDay()->toDateTimeString());

        // 当前时间格式化
        // now()->toDateTimeString(); //精确到秒 2020-01-13 16:06:00
        // Carbon::now()->toDateString();// 2020-01-13
        // Carbon::now()->toDateTimeString();// 精确到秒 2020-01-13 16:06:00

        // Carbon 时间戳转成年月日
        $string = Carbon::createFromTimestamp('时间戳')->toDateTimeString(); // 2012-10-5 23:26:11

        // 增加300秒 显示为5分钟
        // echo Carbon::now()->addSeconds(300)->diffInMinutes(); // 5  (5分钟)
        //
        // // 增加299秒 不足5分钟 显示4分钟59秒
        // echo Carbon::now()->addSeconds(299)->diffForHumans() . "\n"; // 4分钟后
        // echo Carbon::now()->addSeconds(299)->diffForHumans(Carbon::now(), null, true, 2) . "\n"; // 4分钟59秒后
        // echo Carbon::now()->addSeconds(299)->diffForHumans(null, true) . "\n"; // 4分钟
        //
        // // 显示人类容易阅读的时间差
        // echo Carbon::now()->subDays(5)->diffForHumans() . "\n";               // 5天前
        // echo Carbon::now()->addDays(5)->diffForHumans() . "\n";               // 5天后
        //
        // echo Carbon::now()->addSeconds(5)->diffForHumans() . "\n";            // 5秒后 5秒距现在
        //
        // echo Carbon::now()->subSeconds(20)->diffForHumans() . "\n"; // 20秒前
        // echo Carbon::now()->diffForHumans(Carbon::now()->addSeconds(20)) . "\n"; // 20秒前
        // echo Carbon::now()->diffForHumans(Carbon::now()->subSeconds(20)) . "\n";   // 20秒后
        // echo Carbon::now()->addSeconds(20)->diffForHumans() . "\n"; // 20秒后 若是满足显示分钟的条件 则会显示 几分钟后
        // echo Carbon::now()->addSeconds(20)->diffForHumans(null, true) . "\n"; // 20秒


        // 已知时间戳 计算返回 友好的显示多少分钟前
        $timestamp = 1669046400;
        // 1. 计算与当前时间的时间差
        $diff = Carbon::now()->diff($timestamp);

        // 推荐使用 parse 可解析时间戳或字符串格式
        // $diff_month = Carbon::now()::diffInMonths($timestamp); // 差几个月
        $diff_month = Carbon::now()::parse($timestamp)->diffInMonths(); // 差几个月

        dump($diff_month);


        // 根据时间戳 返回 rfc3339标准格式，格式为YYYY-MM-DDTHH:mm:ss+TIMEZONE
        // $time_expire = 1523130077 + 10 * 60;
        // echo $dateRfc3339 = Carbon::createFromTimestamp($time_expire)->toRfc3339String(); // "2018-04-08T03:51:17+08:00"





        $this->assertTrue(true, 'message');
    }
}
