<?php

namespace Hnllyrp\LaravelSupport\Services\Common;

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class JwtService
{
    /**
     * 签发token
     * @param $body
     * @param string $header 保存至 浏览器 header的名称标识
     * @return array
     */
    public static function createToken($body, string $header = 'token')
    {
        $cfg = config('jwt');
        $key = $cfg['key'] ?? 'example_key';

        // 设置过期时间
        $cfg['payload']['exp'] = Carbon::now()->addDays($cfg['expires'])->timestamp;
        // 保存自定义信息 例如 user_id
        $cfg['payload']['body'] = $body ?? [];

        $payload = $cfg['payload'];

        $jwt = JWT::encode($payload, $key, 'HS256');

        return [$header => $jwt, 'expire' => $cfg['payload']['exp']];
    }

    /**
     * 获取token
     * @param $jwt
     * @return int|mixed
     */
    public static function decodeToken($jwt)
    {
        $key = config('jwt.key');

        try {
            $payload = JWT::decode($jwt, new Key($key, 'HS256'));
        } catch (ExpiredException $expiredException) {
            Log::error('JWT decodeToken: ' . $expiredException->getMessage()); // token过期
            return 0;
        } catch (\Exception $e) {
            if (config('app.debug')) {
                Log::error('JWT decodeToken: ' . $e->getMessage());
            }
            return 0;
        }

        return collect($payload)->get('body');
    }

    /**
     * 续签token
     * @param $jwt
     * @param string $header
     * @return array|int
     */
    public static function refreshToken($jwt, string $header = 'token')
    {
        $key = config('jwt.key');

        try {
            $payload = JWT::decode($jwt, new Key($key, 'HS256'));

            $expire = collect($payload)->get('exp', 0);
            if ($expire < Carbon::now()->subDay()->timestamp) {
                // 判断过期时间 提前一天时间刷新token
                $body = collect($payload)->get('body');
                return self::createToken($body, $header);
            }

            return [$header => $jwt, 'expire' => $expire];

        } catch (ExpiredException $expiredException) {
            Log::error('JWT refreshToken : ' . $expiredException->getMessage()); // token过期
            return 0;
        } catch (\Exception $e) {
            if (config('app.debug')) {
                Log::error('JWT refreshToken: ' . $e->getMessage());
            }
            return 0;
        }
    }

    /**
     * 生成一个会员token
     * @param int $user_id
     * @param string $item
     * @return array
     */
    public static function createUserToken($user_id = 0, string $item = 'user_id')
    {
        return self::createToken([$item => $user_id]);
    }

    /**
     * 获取会员token并检查登录是否过期
     * @param null $token
     * @param string $item
     * @param string $header
     * @return int|mixed
     */
    public static function getUserToken($token = null, string $item = 'user_id', string $header = 'token')
    {
        if (is_null($token)) {
            if (request()->hasHeader($header)) {
                $token = request()->header($header);
            } elseif (request()->has($header)) {
                $token = request()->get($header);
            } else {
                $token = request()->bearerToken(); // 推荐 Authorization: Bearer <token>
            }
        }

        if (is_null($token)) {
            return 0;
        }

        $body = self::decodeToken($token);

        return $body ? collect($body)->get($item, 0) : 0;
    }
}
