<?php

namespace Tests\Unit;

use Hnllyrp\LaravelSupport\Services\Common\JwtService;
use PHPUnit\Framework\TestCase;

class JwtTest extends TestCase
{
    /**
     * test jwt
     */
    public function testCreateToken($user_id = '')
    {
        // $token = JwtService::createToken(['user_id' => $user_id]);

        $user_token = JwtService::createUserToken($user_id);

        // $admin_token = JwtService::createUserToken($user_id, 'admin_id');

        return $user_token;
    }

    public function testDecodeToken($token = '')
    {
        $jwt = JwtService::decodeToken($token);

        return $jwt;
    }

    public function testRefreshToken($token = '')
    {
        $token = JwtService::refreshToken($token);

        return $token;
    }

    public function testCheckUserToken($token)
    {
        $user_id = self::getUserToken($token);

        return $user_id;
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

        $payload = JwtService::decodeToken($token);

        $token = $payload ? collect($payload)->get($item) : 0;

        dump('token', $token);

        return $token;
    }
}
