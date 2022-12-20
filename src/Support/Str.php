<?php

namespace Hnllyrp\LaravelSupport\Support;


class Str extends \Illuminate\Support\Str
{

    /**
     * json to array
     * @param string $str
     * @return mixed
     */
    public static function jsonToArr(string $str = '')
    {
        $arr = json_decode($str, true);
        return is_null($arr) ? $str : $arr;
    }

    /**
     * array to json
     * @param array $arr
     * @return false|string
     */
    public static function arrToJson(array $arr = [])
    {
        return json_encode($arr, JSON_UNESCAPED_UNICODE);
    }


    /**
     * 字符串截取，支持中文和其他编码
     * @access public
     * @param string $str 需要转换的字符串
     * @param string $start 开始位置
     * @param string $length 截取长度
     * @param string $charset 编码格式
     * @param string $suffix 截断显示字符 默认 ***
     * @param string $position 截断显示字符位置 默认 1 为中间 例：刘***然，0 为后缀 刘***
     * @return string
     */
    public static function msubstr($str = '', $start = 0, $length = 1, $charset = "utf-8", $suffix = '***', $position = 1)
    {
        if (function_exists("mb_substr")) {
            $slice = mb_substr($str, $start, $length, $charset);
            $slice_end = mb_substr($str, -$length, $length, $charset);
        } elseif (function_exists('iconv_substr')) {
            $slice = iconv_substr($str, $start, $length, $charset);
            $slice_end = iconv_substr($str, -$length, $length, $charset);
        } else {
            $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
            $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
            $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
            $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
            preg_match_all($re[$charset], $str, $match);
            $slice = join("", array_slice($match[0], $start, $length));
            $slice_end = join("", array_slice($match[0], -$length, $length));
        }

        return $position == 0 ? $slice . $suffix : $slice . $suffix . $slice_end;
    }

    /**
     * 将字符串以 * 号格式显示 配合 self::msubstr 函数使用
     * string_to_star($str,1)  w******f , string_to_star($str,2) we****af
     * @param string $string 至少9个字符长度才截取
     * @param int $show_num 前后各保留几个字符
     * @return string
     */
    public static function string_to_star($string = '', $show_num = 3)
    {
        $strlen = strlen($string);
        if ($strlen > 9 && $strlen > $show_num) {
            $star_length = '';
            $length = $strlen - $show_num * 2;
            for ($x = 1; $x <= $length; $x++) {
                $star_length .= "*";
            }
            return self::msubstr($string, 0, $show_num, 'utf-8', $star_length);
        }

        return $string;
    }
}
