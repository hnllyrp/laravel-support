<?php

namespace Hnllyrp\LaravelSupport\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str as BaseStr;

class Str extends BaseStr
{

    /**
     * Get a new stringable object from the given string.
     *
     * @param string $string
     * @return Stringable
     */
    public static function of($string)
    {
        return new Stringable($string);
    }

    /**
     * 生成随机长度位数字
     *
     * @param int $length
     * @return string
     */
    public static function random_int(int $length = 16)
    {
        if (empty($length) || $length == 1 || $length > 100) {
            return 0;
        }

        if ($length > 16) {
            // 方法一
            return substr(str_shuffle(str_repeat($x = '0123456789', ceil($length / strlen($x)))), 1, $length);
        }

        // 方法二 仅适用于1-16位
        try {
            $min = pow(10, $length - 1); // 10的 $length - 1 次方
            $max = pow(10, $length) - 1; // 10的 $length 次方 - 1

            return (string)random_int($min, $max);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * json 转 数组
     * @param $json
     * @param bool $assoc
     * @param int $depth
     * @param int $options
     * @return array|mixed
     */
    public static function json_decode($json, $assoc = false, $depth = 512, $options = 0)
    {
        if (empty($json)) {
            return [];
        }

        if (is_array($json)) {
            return $json;
        }

        $data = json_decode($json, $assoc, $depth, $options);
        if (JSON_ERROR_NONE !== json_last_error()) {
            return [];
        }

        return $data;
    }

    /**
     * 数组 转 json
     * @param $value
     * @param int $flags
     * @param int $depth
     * @return string
     */
    public static function json_encode($value, $flags = 0, $depth = 512)
    {
        if (empty($value)) {
            return '';
        }
        $json = json_encode($value, $flags, $depth);
        if (JSON_ERROR_NONE !== json_last_error()) {
            return '';
        }
        return $json;
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
    public static function mb_substr($str = '', $start = 0, $length = 1, $charset = "utf-8", $suffix = '***', $position = 1)
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
     * 将字符串以 * 号格式显示 配合 self::mb_substr 函数使用
     * str_to_star($str,1)  w******f , str_to_star($str,2) we****af
     * @param string $string 至少9个字符长度才截取
     * @param int $show_num 前后各保留几个字符
     * @return string
     */
    public static function str_to_star($string = '', $show_num = 3)
    {
        $strlen = strlen($string);
        if ($strlen > 9 && $strlen > $show_num) {
            $star_length = '';
            $length = $strlen - $show_num * 2;
            for ($x = 1; $x <= $length; $x++) {
                $star_length .= "*";
            }
            return self::mb_substr($string, 0, $show_num, 'utf-8', $star_length);
        }

        return $string;
    }

    /**
     * @param string|null $url
     * @param array $query
     * @return string
     */
    public static function url_with_query(?string $url, array $query = [])
    {
        if (!$url || !$query) {
            return $url;
        }

        $array = explode('?', $url);

        $url = $array[0];

        parse_str($array[1] ?? '', $originalQuery);

        return $url . '?' . http_build_query(array_merge($originalQuery, $query));
    }

    /**
     * @param string $url
     * @param string|array $keys
     * @return string
     */
    public static function url_without_query($url, $keys)
    {
        if (!\Illuminate\Support\Str::contains($url, '?') || !$keys) {
            return $url;
        }

        if ($keys instanceof Arrayable) {
            $keys = $keys->toArray();
        }

        $keys = (array)$keys;

        $urlInfo = parse_url($url);

        parse_str($urlInfo['query'], $query);

        Arr::forget($query, $keys);

        $baseUrl = explode('?', $url)[0];

        return $query ? $baseUrl . '?' . http_build_query($query) : $baseUrl;
    }

    /**
     * @param array|string $keys
     * @return string
     */
    public static function full_url_without_query($keys)
    {
        return self::url_without_query(request()->fullUrl(), $keys);
    }

    /**
     * @param string $url
     * @param string|array $keys
     * @return bool
     */
    public static function url_has_query(string $url, $keys)
    {
        $value = explode('?', $url);

        if (empty($value[1])) {
            return false;
        }

        parse_str($value[1], $query);

        foreach ((array)$keys as $key) {
            if (Arr::has($query, $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 替换 json 内容
     * @param $search
     * @param $replace
     * @param $subject
     * @return mixed
     */
    public static function replace_json_content($search, $replace, $subject)
    {
        return json_decode(str_replace($search, $replace, json_encode($subject)));
    }

    /**
     * 替换消息变量
     *
     * @param string $template_content
     * @param array $content
     * @param string $pattern
     * @return array|string|string[]|null
     */
    public static function replace_template_content($template_content = '', $content = [], $pattern = '')
    {
        if (empty($template_content)) {
            return '';
        }
        if (empty($content)) {
            return $template_content;
        }

        /**
         * $pattern = '/\${(\w+)}/'; // 匹配变量 ${code}
         * $pattern = '/{\$(\w+)}/'; // 匹配变量 {$code}
         */
        if (empty($pattern)) {
            $pattern = '/\${(\w+)}/'; // 匹配变量 ${code}
        }

        return preg_replace_callback($pattern, function ($matches) use ($content) {
            return $content[$matches[1]];
        }, $template_content);
    }

    /**
     * 替换消息变量
     *  例如：${code}
     *
     * @param string $template_content
     * @param array $content
     * @return array|string|string[]
     */
    public static function replace_template_content_var($template_content = '', $content = [])
    {
        if (empty($template_content)) {
            return '';
        }

        // 替换消息变量 ${code}
        preg_match_all('/\${(.*?)}/', $template_content, $matches);
        foreach ($matches[1] as $vo) {
            $template_content = str_replace('${' . $vo . '}', $content[$vo], $template_content);
        }

        return $template_content;
    }




}
