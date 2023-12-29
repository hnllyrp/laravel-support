<?php

namespace Hnllyrp\LaravelSupport\Services\Common;

use Hnllyrp\LaravelSupport\Support\Abstracts\Service;
use Illuminate\Filesystem\Filesystem;
use Rtgm\sm\RtSm2;
use Rtgm\sm\RtSm3;
use Rtgm\sm\RtSm4;

/**
 * Class SmService
 * 国密sm2,sm3,sm4实现，依赖包 composer require lpilp/guomi
 * 要求 php 扩展 extension=openssl,extension=gmp
 */
class SmService extends Service
{
    /**
     * @var RtSm2
     */
    protected static $sm2;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    protected $private_key_file;

    protected $public_key_file;

    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    public static function sm2($formatSign = 'hex')
    {
        if (is_null(static::$sm2)) {
            static::$sm2 = new RtSm2($formatSign, false);
        }

        return static::$sm2;
    }

    /**
     * sm2 创建密钥对
     * @return array
     */
    public function sm2_create_keys($force = false)
    {
        // 生成目录
        $path = $this->getPath();
        $this->files->ensureDirectoryExists($path);

        $private_key_file = $path . ($this->getPrivateKeyFile() ?? 'api_sm2_private_key.der');
        $public_key_file = $path . ($this->getPublicKeyFile() ?? 'api_sm2_public_key.der');

        if (!file_exists($private_key_file) || $force === true) {
            // 随机生成一对16进制明文公私钥
            list($private_key, $public_key) = self::sm2()->generatekey();

            $this->files->put($private_key_file, $private_key);
            $this->files->put($public_key_file, $public_key);
        } else {
            $public_key = $this->files->get($public_key_file);
        }

        return $public_key;
    }

    /**
     * sm2 公钥加密
     * @param $plaintext
     * @return string
     */
    public function sm2_public_encrypt($plaintext = '')
    {
        if (empty($plaintext)) {
            return '';
        }

        $path = $this->getPath();
        // 16进制明文公钥
        $public_key_file = $path . ($this->getPublicKeyFile() ?? 'api_sm2_public_key.der');
        $public_key = $this->files->get($public_key_file);

        $ciphertext = self::sm2()->doEncrypt($plaintext, $public_key);

        return base64_encode(hex2bin($ciphertext));
    }

    /**
     * sm2 私钥解密
     * @param mixed $ciphertext 需要解密的数据
     * @return string
     */
    public function sm2_private_decrypt($ciphertext = '')
    {
        if (empty($ciphertext)) {
            return '';
        }

        $path = $this->getPath();
        // 16进制明文私钥
        $private_key_file = $path . ($this->getPrivateKeyFile() ?? 'api_sm2_private_key.der');
        $private_key = $this->files->get($private_key_file);

        return self::sm2()->doDecrypt(bin2hex(base64_decode($ciphertext)), $private_key);
    }

    /**
     * sm2 私钥加签
     * @param $data
     * @param string $private_key
     * @return string|null
     */
    public static function sm2_sign($data, $private_key = '')
    {
        if (!is_string($data)) {
            return '';
        }

        $sign = self::sm2()->doSign($data, $private_key);
        return $sign ?? '';
    }

    /**
     * sm2 公钥验签
     * @param $data
     * @param $sign
     * @param string $public_key
     * @return bool
     */
    public static function sm2_verify($data, $sign, $public_key = '')
    {
        if (!is_string($data)) {
            return false;
        }

        return (bool)self::sm2()->verifySign($data, $sign, $public_key);
    }

    /**
     * sm3 生成消息摘要
     * @param $content
     * @return string
     */
    public static function sm3_hash($content)
    {
        $sm3 = new RtSm3();
        return $sm3->digest($content, 1);
    }

    /**
     * sm4 加密
     * @param $content
     * @param string $cipher
     * @param string $format
     * @return false|string
     */
    public static function sm4_encrypt($content, $cipher = 'sm4', $format = 'hex')
    {
        $key = config('gm.sm4.key', '0123456789abcdef');
        $iv = config('gm.sm4.iv', '1234567887654321');

        $sm4 = new RtSm4($key);

        try {
            return $sm4->encrypt($content, $cipher, $iv, $format);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * sm4 解密
     * @param $content
     * @param string $cipher
     * @param string $format
     * @return false|string
     */
    public static function sm4_decrypt($content, $cipher = 'sm4', $format = 'hex')
    {
        $key = config('gm.sm4.key', '0123456789abcdef');
        $iv = config('gm.sm4.iv', '1234567887654321');

        $sm4 = new RtSm4($key);

        try {
            return $sm4->decrypt($content, $cipher, $iv, $format);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @return mixed
     */
    public function getPath()
    {
        return storage_path('app/certs') . '/';
    }

    /**
     * @return mixed
     */
    public function getPrivateKeyFile()
    {
        return $this->private_key_file;
    }

    /**
     * @param mixed $private_key_file
     */
    public function setPrivateKeyFile($private_key_file)
    {
        $this->private_key_file = $private_key_file;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPublicKeyFile()
    {
        return $this->public_key_file;
    }

    /**
     * @param mixed $public_key_file
     */
    public function setPublicKeyFile($public_key_file)
    {
        $this->public_key_file = $public_key_file;
        return $this;
    }
}
