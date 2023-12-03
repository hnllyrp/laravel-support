<?php


namespace Hnllyrp\LaravelSupport\Services\Common;

use Hnllyrp\LaravelSupport\Support\Abstracts\Service;
use Hnllyrp\PhpSupport\Crypt\RSA;
use Illuminate\Filesystem\Filesystem;

class RsaService extends Service
{
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

    /**
     * 生成密钥对，并返回公钥
     *
     * @param int $bits
     * @param false $force 是否重新生成
     * @return mixed|string
     */
    public function create_keys(int $bits = 1024, bool $force = false)
    {
        // 生成目录
        $path = $this->getPath();
        $this->files->ensureDirectoryExists($path);

        $private_key_file = $path . ($this->getPrivateKeyFile() ?? 'api_rsa_private_key.pem');
        $public_key_file = $path . ($this->getPublicKeyFile() ?? 'api_rsa_public_key.pem');

        if (!file_exists($private_key_file) || $force === true) {
            // 创建密钥对
            $res = RSA::create_keys($bits);

            $public_key = $res['public_key'];
            $private_key = $res['private_key'];

            $this->files->put($private_key_file, $private_key);
            $this->files->put($public_key_file, $public_key);
        } else {
            $public_key = $this->files->get($public_key_file);
        }

        return $public_key;
    }

    /**
     * 公钥加密
     *
     * @param mixed $plaintext 需要加密的数据 string or array
     * @return string
     */
    public function public_encrypt($plaintext)
    {
        if (empty($plaintext)) {
            return '';
        }

        $path = $this->getPath();

        $public_key_file = $path . ($this->getPublicKeyFile() ?? 'api_rsa_public_key.pem');

        $public_key = $this->files->get($public_key_file);

        return RSA::public_encrypt($plaintext, $public_key);
    }

    /**
     * 私钥解密
     * @param mixed $ciphertext 需要解密的数据
     * @return string|null
     */
    public function private_decrypt($ciphertext)
    {
        if (empty($ciphertext)) {
            return '';
        }

        $path = $this->getPath();

        $private_key_file = $path . ($this->getPrivateKeyFile() ?? 'api_rsa_private_key.pem');
        $private_key = $this->files->get($private_key_file);

        return RSA::private_decrypt($ciphertext, $private_key);
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
    public function setPrivateKeyFile($private_key_file): RsaService
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
    public function setPublicKeyFile($public_key_file): RsaService
    {
        $this->public_key_file = $public_key_file;
        return $this;
    }
}
