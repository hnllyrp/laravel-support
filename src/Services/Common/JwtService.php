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
        $cfg['payload']['body'] = $body;

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
            $data = JWT::decode($jwt, new Key($key, 'HS256'));
        } catch (ExpiredException $expiredException) {
            Log::error('JWTDecode: ' . $expiredException->getMessage()); // token过期
            return 0;
        } catch (\Exception $e) {
            if (config('app.debug')) {
                Log::error('JWTDecode: ' . $e->getMessage());
            }
            return 0;
        }

        return collect($data)->get('body');
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
    public static function checkUserToken($token = null, string $item = 'user_id', string $header = 'token')
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

        $payload = self::decodeToken($token);

        return $payload ? collect($payload)->get($item) : 0;
    }
}
