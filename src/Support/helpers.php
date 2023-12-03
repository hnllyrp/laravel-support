<?php

use Illuminate\Support\Facades\Storage;


if (!function_exists('windows_os')) {
    /**
     * Determine whether the current environment is Windows based.
     *
     * @return bool
     */
    function windows_os()
    {
        return PHP_OS_FAMILY === 'Windows';
    }
}

if (!function_exists('storage_public')) {
    /**
     * 用户资源目录
     * @param $path
     * @return mixed
     */
    function storage_public($path = '')
    {
        return Storage::disk('public')->path($path);
    }
}

if (!function_exists('storage_url')) {
    /**
     * 用户资源url
     * @param $value
     * @return mixed
     */
    function storage_url($value = '')
    {
        return $value ? Storage::url($value) : '';
    }
}

if (!function_exists('get_image_path')) {
    /**
     * 获得图片地址  支持本地图片与远程图片
     * @param string $image 图片地址
     * @return mixed|string
     */
    function get_image_path($image = '')
    {
        if (empty($image)) {
            return asset(config('shop.no_picture', 'img/no_image.jpg'));
        }

        // 远程图片 http or https
        if (strtolower(substr($image, 0, 4)) == 'http') {
            return $image;
        }

        return Storage::url($image);
    }
}
if (!function_exists('get_endpoint')) {
    function get_endpoint($image = '')
    {
        return Storage::url($image ?: '/');
    }
}

if (!function_exists('price_format')) {
    /**
     * 格式化商品价格
     *
     * @param float $price 商品价格
     * @return  string
     */
    function price_format($price = 0, $change_price = true)
    {
        $price = $price ?? 0;

        $cfg_price_format = config('shop.price_format', 0);
        $cfg_currency_format = config('shop.currency_format', '¥%s');

        if ($change_price) {
            switch ($cfg_price_format) {
                case 0:
                    $price = number_format($price, 2, '.', '');
                    break;
                case 1: // 保留不为 0 的尾数
                    $price = preg_replace('/(.*)(\\.)([0-9]*?)0+$/', '\1\2\3', number_format($price, 2, '.', ''));

                    if (substr($price, -1) == '.') {
                        $price = substr($price, 0, -1);
                    }
                    break;
                case 2: // 不四舍五入，保留1位
                    $price = substr(number_format($price, 2, '.', ''), 0, -1);
                    break;
                case 3: // 直接取整
                    $price = intval($price);
                    break;
                case 4: // 四舍五入，保留 1 位
                    $price = number_format($price, 1, '.', '');
                    break;
                case 5: // 先四舍五入，不保留小数
                    $price = round($price);
                    break;
            }
        } else {
            $price = number_format($price, 2, '.', '');
        }

        // 是否显示 ¥ 前缀
        if (config('shop.show_currency_format', false) == true) {
            return sprintf($cfg_currency_format, $price);
        }

        return $price;
    }
}

if (!function_exists('db_table')) {
    /**
     * 获取数据表名
     * @param $table
     * @return string
     */
    function db_table($table)
    {
        $connection = config('database.default', 'mysql');

        return config('database.connections.' . $connection . '.prefix') . $table;
    }
}

if (!function_exists('db_config')) {
    /**
     * 返回数据库配置信息
     * @param null $item
     * @return \Illuminate\Config\Repository|mixed
     */
    function db_config($item = null)
    {
        $connection = config('database.default', 'mysql');

        $connections = config('database.connections.' . $connection);

        return is_null($item) ? $connections : $connections[$item];
    }
}

if (!function_exists('db_query')) {
    /**
     * 调试打印查询sql, 执行时间
     *
     * 使用方法：添加在查询语言代码前，查询之后断点查看
     * db_query();
     * select * from ;
     * dd('--');
     */
    function db_query($log = false)
    {
        \Illuminate\Support\Facades\DB::listen(
            function (\Illuminate\Database\Events\QueryExecuted $query) use ($log) {
                foreach ($query->bindings as $i => $binding) {
                    if ($binding instanceof \DateTime) {
                        $query->bindings[$i] = $binding->format('\'Y-m-d H:i:s\'');
                    } else {
                        if (is_string($binding)) {
                            $query->bindings[$i] = "'$binding'";
                        }
                    }
                }

                // Insert bindings into query
                $sql = str_replace(array('%', '?'), array('%%', '%s'), $query->sql);
                $singleSql = vsprintf($sql, $query->bindings);

                $sqlTime = 'time:' . $query->time . 'ms';// // sql 执行时间： 单位 毫秒

                if ($log == true) {
                    \Illuminate\Support\Facades\Log::channel('daily')->debug('================================');
                    \Illuminate\Support\Facades\Log::channel('daily')->debug($singleSql);
                    \Illuminate\Support\Facades\Log::channel('daily')->debug($sqlTime);

                    if ($query->time > 1000) {
                        \Illuminate\Support\Facades\Log::channel('daily')->debug('慢查询：');
                        \Illuminate\Support\Facades\Log::channel('daily')->debug($singleSql);
                        \Illuminate\Support\Facades\Log::channel('daily')->debug($sqlTime);
                    }
                } else {
                    dump($singleSql);
                    dump($sqlTime);

                    if ($query->time > 1000) {
                        dump('慢查询：');
                        dump($singleSql);
                        dump($sqlTime);
                    }
                }
            }
        );
    }
}

if (!function_exists('db_log')) {
    /**
     * DB sql 日志记录
     */
    function db_log()
    {
        db_query(true);
    }
}

if (!function_exists('is_wechat_browser')) {
    /**
     * 检查是否是微信浏览器访问
     * @return bool
     */
    function is_wechat_browser()
    {
        $user_agent = strtolower(request()->userAgent());

        if (strpos($user_agent, 'micromessenger') === false) {
            return false;
        } else {
            return true;
        }
    }
}

if (!function_exists('is_ssl')) {
    /**
     * 判断是否SSL协议  https://
     * @return boolean
     */
    function is_ssl()
    {
        return request()->isSecure();
    }
}

if (!function_exists('html_in')) {
    /**
     * html代码输入
     *
     * @param $str
     *
     * @return string
     */
    function html_in($str = '')
    {
        $str = trim($str);

        return addslashes(e($str));
    }
}

if (!function_exists('html_out')) {
    /**
     * html代码输出
     *
     * @param $str
     *
     * @return string
     */
    function html_out($str = '')
    {
        if (function_exists('htmlspecialchars_decode')) {
            $str = htmlspecialchars_decode($str);
        } else {
            $str = html_entity_decode($str);
        }

        return stripslashes($str);
    }
}

if (!function_exists('escape_html')) {
    /**
     * HTML代码过滤
     * @param string $str 字符串
     * @return string
     */
    function escape_html($str)
    {
        $search = array(
            "'<script[^>]*?>.*?</script>'si",  // 去掉 javascript
            "'<iframe[^>]*?>.*?</iframe>'si", // 去掉iframe
        );

        return preg_replace($search, '', $str);
    }
}

if (!function_exists('new_html_in')) {
    /**
     *  处理post get输入参数 兼容php5.4以上magic_quotes_gpc后默认开启后 处理重复转义的问题
     * @return string $str
     */
    function new_html_in($str)
    {
        return htmlspecialchars(trim($str));
    }
}

if (!function_exists('realpath_curl')) {
    /**
     * 处理微信素材路径 兼容php5.6+
     * @param string $file 图片完整路径 exp:D:/www/data/123.png
     */
    function realpath_curl($file)
    {
        if (class_exists('\CURLFile')) {
            return new \CURLFile(realpath($file));
        } else {
            return '@' . realpath($file);
        }
    }
}

if (!function_exists('getClientIp')) {
    /**
     * 获取客户端ip
     * @return string
     */
    function getClientIp(): string
    {
        return request()->getClientIp();
    }
}

if (!function_exists('getServerIp')) {
    /**
     * 获取服务端ip
     * @return mixed|string
     */
    function getServerIp()
    {
        if (request()->server()) {
            if (request()->server('SERVER_ADDR')) {
                // HTTP请求可以获取, 但CLI不行
                $server_ip = request()->server('SERVER_ADDR');
            } elseif (request()->server('SERVER_NAME')) {
                $server_ip = gethostbyname(request()->server('SERVER_NAME'));
            } else {
                $server_ip = request()->server('LOCAL_ADDR');
            }
        } else {
            // HTTP请求可以获取, 但CLI不行
            $server_ip = getenv('SERVER_ADDR');
        }

        if (!$server_ip) {
            // 兼容获取CLI方式下服务器IP, 注意云主机有可能只能获取到内网IP
            return getHostByName(getHostName());
        }
        return filter_var($server_ip, FILTER_VALIDATE_IP) ?: '127.0.0.1';
    }
}

