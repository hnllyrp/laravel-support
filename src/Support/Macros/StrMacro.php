<?php


namespace Hnllyrp\LaravelSupport\Support\Macros;


use Illuminate\Support\Str;

class StrMacro
{
    public static function appendIf(): callable
    {
        return function ($value, $suffix) {
            return Str::endsWith($value, $suffix) ? $value : $value . $suffix;
        };
    }

    public static function prependIf(): callable
    {
        return function ($value, $prefix) {
            return Str::startsWith($value, $prefix) ? $value : $prefix . $value;
        };
    }

    public static function mbSubstrCount(): callable
    {
        return function ($haystack, $needle, $encoding = null) {
            return mb_substr_count($haystack, $needle, $encoding);
        };
    }

    public static function pipe(): callable
    {
        return function ($value, callable $callback) {
            return $callback($value);
        };
    }

    /**
     * @see https://github.com/koenhendriks/laravel-str-acronym
     */
    public static function acronym(): callable
    {
        return function ($string, $delimiter = '') {
            if (empty($string)) {
                return '';
            }

            $acronym = '';
            foreach (preg_split('/[^\p{L}]+/u', $string) as $word) {
                if (!empty($word)) {
                    $first_letter = mb_substr($word, 0, 1);
                    $acronym .= $first_letter . $delimiter;
                }
            }

            return $acronym;
        };
    }
}
