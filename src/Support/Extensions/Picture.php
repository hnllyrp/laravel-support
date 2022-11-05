<?php

namespace Hnllyrp\LaravelSupport\Support\Extensions;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class Picture
{
    /**
     * @param string $base64
     * @param string $savePath
     * @param array|string[] $accept_extension
     * @return false|string
     */
    public static function base64ToImg(string $base64 = '', string $savePath = '/', array $accept_extension = ['jpeg', 'png', 'bmp', 'gif', 'svg', 'webp'])
    {
        $pattern = '/^(data:\s*image\/(\w+);base64,)/';

        if (preg_match($pattern, $base64, $matches)) {
            $content = base64_decode(str_replace($matches[1], '', $base64));

            if (empty($savePath)) {
                return $content;
            }

            $extension = $matches[2];
            if ($savePath && in_array($extension, $accept_extension)) {
                $img_path = rtrim($savePath, '/') . '/' . Str::random(30) . '.' . $extension;
                Storage::disk('public')->put($img_path, $content);

                return $img_path;
            }
        }

        return $base64;
    }

    /**
     * @param string $img_path
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public static function imgToBase64(string $img_path = '/')
    {
        if (empty($img_path)) {
            return '';
        }

        $basename = basename($img_path);
        [$name, $extension] = explode('.', $basename);

        $content = Storage::disk('public')->get($img_path);
        //获取图片base64
        $base64 = base64_encode($content);

        return 'data:image/' . $extension . ';base64,' . $base64;
    }
}
