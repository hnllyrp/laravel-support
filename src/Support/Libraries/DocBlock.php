<?php

namespace Hnllyrp\LaravelSupport\Support\Libraries;

/**
 * 一个简单的获取方法注释类
 *
 * $doc = DocBlock::formatDoc($methodDoc);
 */
class DocBlock
{
    /**
     * 格式化注释.
     *
     * @param $haystack
     * @return bool|mixed
     */
    public static function formatDoc($haystack)
    {
        //格式错误
        if (false === preg_match('#^/\*\*(.*)\*/#s', $haystack, $comment)) {
            return false;
        }

        //移除 符号 *
        if (false === preg_match_all('#^\s*\*(.*)#m', trim($comment[1]), $lines)) {
            return false;
        } else {
            return $lines[1];
        }
    }

    /**
     * 格式化名称.
     *
     * @param $haystack
     * @return mixed
     */
    public static function formatName($haystack)
    {
        $temp = explode('\\', $haystack);

        return end($temp);
    }

    /**
     * 格式化中文名称.
     *
     * @param $haystack
     * @param null $prefix
     * @return string|null
     */
    public static function formatTitle($haystack, $prefix = null)
    {
        $title = count($haystack) > 0 ? trim($haystack[0]) : null;

        return $title;
    }

    /**
     * 格式化描述.
     *
     * @param $haystack
     * @return array
     */
    public static function formatDesc($haystack)
    {
        $reg = '/@desc.*/i';
        $desc = [];

        foreach ($haystack as $line) {
            if (false !== preg_match($reg, trim($line), $tmp)) {
                if (!empty($tmp)) {
                    $desc[] = trim(str_replace('@desc', '', $tmp[0]));
                }
            }
        }

        return $desc;
    }

    /**
     * 格式化参数.
     *
     * @param $haystack
     * @return array
     */
    public static function formatParams($haystack)
    {
        $reg = '/@param.*/i';
        $params = [];

        $i = 0;
        foreach ($haystack as $line) {
            if (false !== preg_match($reg, trim($line), $tmp)) {
                if (!empty($tmp)) {
                    //  @param integer $user_id 1 0 用户id
                    $param = trim(str_replace('@param', '', $tmp[0]));
                    $params[$i] = $param;

                    ++$i;
                }
            }
        }

        return $params;
    }

    /**
     * 格式化返回.
     *
     * @param $haystack
     * @return array
     */
    public static function formatReturns($haystack)
    {
        $reg = '/@return.*/i';
        $returns = [];

        foreach ($haystack as $i => $line) {
            if (false !== preg_match($reg, trim($line), $tmp)) {
                if (!empty($tmp)) {
                    $temp = explode(' ', trim(str_replace('@return', '', $tmp[0])));

                    $returns[$i] = json_encode($temp);
                }
            }
        }

        sort($returns);

        return $returns;
    }

    /**
     * 格式化默认权限.
     *
     * @desc 格式化默认权限
     * @param $haystack
     * @return bool
     */
    public static function formatDefault($haystack)
    {
        $reg = '/@default.*/i';

        foreach ($haystack as $line) {
            if (1 === preg_match($reg, trim($line), $tmp)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 格式化禁用权限.
     *
     * @desc 格式化禁用权限
     * @param $haystack
     * @return bool
     */
    public static function formatBlack($haystack)
    {
        $reg = '/@black.*/i';

        foreach ($haystack as $line) {
            if (1 === preg_match($reg, trim($line), $tmp)) {
                return true;
            }
        }

        return false;
    }
}
