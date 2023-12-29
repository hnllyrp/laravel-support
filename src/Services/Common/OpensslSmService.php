<?php

namespace Hnllyrp\LaravelSupport\Services\Common;

use Hnllyrp\LaravelSupport\Support\Abstracts\Service;
use Hnllyrp\PhpSupport\Crypt\SM3;
use Hnllyrp\PhpSupport\Crypt\SM4;

/**
 * Class OpensslSmService
 * 国密算法 sm3,sm4的openssl实现, sm2 默认的openssl 1.1.1版本暂不支持加解密算法
 */
class OpensslSmService extends Service
{

    /**
     * sm2 创建密钥对
     * @return array
     */
    public static function sm2_create_keys()
    {
        $config = array(
            "private_key_type" => OPENSSL_KEYTYPE_EC,
            "curve_name" => "SM2"
        );

        //创建公钥和私钥   返回资源
        $resource = openssl_pkey_new($config);
        //从得到的资源中获取私钥，把私钥赋给 $private_key
        openssl_pkey_export($resource, $private_key, null, $config);

        //从得到的资源中获取公钥，返回公钥 $public_key
        $key = openssl_pkey_get_details($resource);
        $public_key = $key['key'] ?? '';

        return ['public_key' => $public_key, 'private_key' => $private_key];
    }

    /**
     * sm3 生成消息摘要
     * @param $data
     * @return false|string
     */
    public static function sm3_hash($data)
    {
        return SM3::openssl_sm3($data);
    }

    /**
     * sm4 加密
     * @param $data
     * @param string $cipher
     * @param string $format
     * @return false|string
     */
    public static function sm4_encrypt($data, $cipher = 'sm4', $format = 'hex')
    {
        $key = config('gm.sm4.key', '0123456789abcdef');
        $iv = config('gm.sm4.iv', '1234567887654321');

        $sm4 = new SM4($key, $iv);

        return $sm4->encrypt($data, $cipher, $format);
    }

    /**
     * sm4 解密
     * @param $data
     * @param string $cipher
     * @param string $format
     * @return false|string
     */
    public static function sm4_decrypt($data, $cipher = 'sm4', $format = 'hex')
    {
        $key = config('gm.sm4.key', '0123456789abcdef');
        $iv = config('gm.sm4.iv', '1234567887654321');

        $sm4 = new SM4($key, $iv);

        return $sm4->decrypt($data, $cipher, $format);
    }

}
