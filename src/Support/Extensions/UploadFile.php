<?php

namespace Hnllyrp\LaravelSupport\Support\Extensions;

use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * 文件上传下载类
 *
 * Class File
 * @package App\Extensions
 */
class UploadFile
{

    /**
     * 上传文件（可上传到本地服务器或OSS）
     * @param string $savePath
     * @param bool $isHasName 是否保留原文件名
     * @return array
     * @throws Exception
     */
    public static function upload($savePath = '', $isHasName = true)
    {
        $files = request()->file();
        if ($files) {
            if ($savePath && !Storage::exists($savePath)) {
                Storage::makeDirectory($savePath);
            }

            $disk = self::diskFile();

            $res = [];
            foreach ($files as $key => $file) {

                if ($file) {
                    if (is_array($file)) {
                        foreach ($file as $idx => $v) {
                            if ($v->isValid()) {

                                if ($isHasName == true) {
                                    $file_name = date('Ymd') . '_' . (30) . '.' . $v->clientExtension();
                                } else {
                                    // 原文件名
                                    $file_name = $v->getClientOriginalName();
                                }

                                $path[$key][$idx] = $v->storeAs($savePath, $file_name, $disk);
                                if ($path[$key][$idx]) {
                                    // 上传成功 获取上传文件信息
                                    $res[$key][$idx]['error'] = 0;
                                    $res[$key][$idx]['url'] = Storage::disk($disk)->url($path[$key][$idx]);
                                    $res[$key][$idx]['file_path'] = rtrim($savePath, '/') . '/' . $file_name; // data/wewe123.jpg
                                    $res[$key][$idx]['file_name'] = $file_name;
                                    $res[$key][$idx]['size'] = $v->getSize();
                                    $res[$key][$idx]['fileinfo'] = $v->getFileInfo(); //文件信息
                                } else {
                                    // 上传错误提示
                                    $res[$key][$idx]['error'] = $v->getError();
                                    $res[$key][$idx]['message'] = $v->getErrorMessage();
                                }
                            }
                        }
                    } else {
                        if ($file->isValid()) {

                            if ($isHasName == true) {
                                $file_name = date('Ymd') . '_' . Str::random(30) . '.' . $file->clientExtension();
                            } else {
                                // 原文件名
                                $file_name = $file->getClientOriginalName();
                            }

                            $path[$key] = $file->storeAs($savePath, $file_name, $disk);
                            if ($path[$key]) {
                                // 上传成功 获取上传文件信息
                                $res[$key]['error'] = 0;
                                $res[$key]['url'] = Storage::disk($disk)->url($path[$key]);
                                $res[$key]['file_path'] = rtrim($savePath, '/') . '/' . $file_name; // data/wewe123.jpg
                                $res[$key]['file_name'] = $file_name;
                                $res[$key]['size'] = $file->getSize();
                                $res[$key]['fileinfo'] = $file->getFileInfo(); //文件信息
                            } else {
                                // 上传错误提示
                                $res[$key]['error'] = $file->getError();
                                $res[$key]['message'] = $file->getErrorMessage();
                            }
                        }
                    }
                }
            }

            return $res;
        }

        return ['error' => 1, 'message' => 'upload fail'];
    }

    /**
     * 上传图片(单文件)
     *
     * @param string $savePath
     * @param array $up_config
     * @param string $key
     * @return array
     * @throws Exception
     */
    public static function upload_image($savePath = '', $up_config = [], $key = 'file')
    {
        if (empty($key)) {
            return ['error' => 1, 'message' => 'upload file is empty'];
        }

        $file = request()->file($key);
        if ($file) {
            if (!$savePath || !$file->isWritable()) {
                return ['error' => 1, 'message' => 'upload path is not writable'];
            }

            if ($file->isValid()) {
                if (!Storage::exists($savePath)) {
                    Storage::makeDirectory($savePath);
                }

                // 验证文件大小
                $file_size = $up_config['maxSize'] ?? 2 * 1024 * 1024;
                if ($file->getSize() > $file_size) {
                    return ['error' => 1, 'message' => trans('file.file_size_limit')];
                }

                // 验证文件格式
                $file_type = $up_config['mimeType'] ?? ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($file->getClientMimeType(), $file_type)) {
                    return ['error' => 1, 'message' => trans('file.not_file_type')];
                }

                $file_name_prefix = $up_config['fileNamePrefix'] ?? '';
                if ($file_name_prefix) {
                    $file_name = $file_name_prefix . '.' . $file->clientExtension();
                } else {
                    $file_name = date('Ymd') . '_' . Str::random(30) . '.' . $file->clientExtension();
                }

                $disk = self::diskFile();
                $path = $file->storeAs($savePath, $file_name, ['disk' => $disk]);
                if ($path) {
                    // 上传成功 获取上传文件信息
                    $res['error'] = 0;
                    $res['url'] = Storage::disk($disk)->url($path);
                    $res['file_path'] = rtrim($savePath, '/') . '/' . $file_name; // data/wewe123.jpg
                    $res['file_name'] = $file_name;
                    $res['size'] = $file->getSize();
                    $res['path'] = $path;
                    $res['file_info'] = $file->getFileInfo(); //文件信息
                    return $res;
                } else {
                    // 上传错误提示
                    return ['error' => $file->getError(), 'message' => $file->getErrorMessage()];
                }
            }
        }

        return ['error' => 1, 'message' => 'upload fail'];
    }

    /**
     * 删除文件（可删除本地服务器文件或OSS文件）
     * @param string $file 相对路径 data/attached/article/pOFEQJ3wSab1vhsrCVr5k6eU2m7e1bQ7W16dcc14.jpeg
     * @param array $except 排除文件名数组
     * @return bool
     */
    public static function remove($file = '', $except = ['no_image', 'errorImg'])
    {
        if (empty($file) || in_array($file, ['/', '\\'])) {
            return false;
        }

        // 排除 不需要删除的文件
        if (is_string($file)) {
            // 单文件
            $contains = Str::contains($file, $except);
            if ($contains == true) {
                return false;
            }
        }

        if (is_array($file)) {
            // 多文件
            foreach ((array)$file as $k => $v) {
                if (!empty($v) && Str::contains($v, $except)) {
                    unset($file[$k]);
                }
            }
        }

        $disk = self::diskFile();
        // 开启oss 同时删除本地文件
        if ($disk != 'public') {
            Storage::disk('public')->delete($file);
        }

        return Storage::disk($disk)->delete($file);
    }

    /**
     * 下载服务器文件到本地
     *
     * @param string $file
     * @return bool|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public static function fileDownload($file = '')
    {
        if (empty($file)) {
            return false;
        }

        $disk = 'public';
        $exists = Storage::disk($disk)->exists($file);
        if ($exists) {
            return Storage::disk($disk)->download($file);
        }
        return false;
    }

    /**
     * 附件镜像到阿里云OSS
     * @param string $file 文件相对路径 如 data/attend/1.jpg
     * @param bool $is_delete 是否要删除本地图片
     * @return string
     * @throws FileNotFoundException
     */
    public static function ossMirror($file = '', $is_delete = false, $is_excel = false)
    {
        if (empty($file)) {
            return false;
        }

        $config = config('shop');

        if (isset($config['open_oss']) && $config['open_oss'] == 1) {
            $exists = Storage::disk('public')->exists($file);
            if ($exists) {
                $disk = self::diskFile();

                // oss 若存在则覆盖原文件
                $fileContents = Storage::disk('public')->get($file);
                Storage::disk($disk)->put($file, $fileContents);

                if ($is_delete == true) {
                    Storage::disk('public')->delete($file); // 删除本地
                }
            }
        }

        return $file;
    }

    /**
     * 同步上传服务器图片到OSS
     * @param array $file_list 图片列表 如 array('0'=>'data/attend/1.jpg', '1'=>'data/attend/2.png')
     * @param bool $is_delete 是否要删除本地图片
     * @return bool
     * @throws FileNotFoundException
     */
    public static function batchUploadOss($file_list = [], $is_delete = false)
    {
        if (empty($filelist)) {
            return false;
        }

        $config = config('shop');

        // 开启OSS
        if (isset($config['open_oss']) && $config['open_oss'] == 1) {
            foreach ($file_list as $k => $file) {
                $image_name = self::ossMirror($file);
                if ($is_delete == true) {
                    Storage::disk('public')->delete($file); // 删除本地
                }
            }
            return isset($image_name) ? true : false;
        }
    }

    /**
     * 同步下载OSS图片到本地服务器
     * @param array $file_list 图片列表 如 array('0'=>'data/attend/1.jpg', '1'=>'data/attend/2.png')
     * @return bool
     * @throws FileNotFoundException
     */
    public static function batchDownloadOss($file_list = [])
    {
        if (empty($file_list)) {
            return false;
        }

        $config = config('shop');

        // 开启OSS
        if (isset($config['open_oss']) && $config['open_oss'] == 1) {
            $disk = self::diskFile();

            foreach ($file_list as $k => $file) {
                $exist_oss = Storage::disk($disk)->exists($file);
                if ($exist_oss) {
                    $fileContents = Storage::disk($disk)->get($file);

                    Storage::disk('public')->put($file, $fileContents);
                }
            }
            return true;
        }
    }

    /**
     * 文件类型存储位置
     *
     * @param string $disk
     * @return string
     * @throws Exception
     */
    public static function diskFile($disk = '')
    {
        if (empty($disk)) {
            $config = config('shop');

            $disk = 'public'; //本地
            if (isset($config['open_oss']) && $config['open_oss'] == 1) {
                $cloud_storage = $config['cloud_storage'] ?? 0;
                if ($cloud_storage == 1) {
                    $disk = 'obs'; //华为云
                } elseif ($cloud_storage == 2) {
                    $disk = 'cos'; //腾讯云
                } else {
                    $disk = 'oss'; //阿里云
                }
            }
        }

        return $disk;
    }

    /**
     * api 上传素材
     * @param string $savePath 保存目录
     * @param string $type 上传类型 image、video
     * @param int $user_id 用户id 用于作标识
     * @return array|bool
     */
    public static function api_upload($savePath = '', $type = 'image', $user_id = 0)
    {
        if (empty($savePath)) {
            return false;
        }

        $urls = [];
        if (request()->hasFile('file')) {
            $disk = self::diskFile();

            $path = request()->file('file')->store($savePath, $disk);

            array_push($urls, $path);

            return $urls;
        } else {
            // 接收前端图片 base64 内容
            $files = request()->get('file');

            if (is_null($files)) {
                return false;
            }

            $items = (count($files) > 1) ? $files : [$files];

            if ($type == 'video') {
                $pattern = '/^(data:\s*video\/(\w+);base64,)/';
            } else {
                $pattern = '/^(data:\s*image\/(\w+);base64,)/';
            }

            $disk = self::diskFile();

            foreach ($items as $item) {
                // 保存到存储
                $content = $item['content'] ?? '';
                if (preg_match($pattern, $content, $matches)) {
                    $content = base64_decode(str_replace($matches[1], '', $content));
                    $extension = strtolower($matches[2]);

                    if (in_array($extension, ['jpeg', 'jpg', 'png', 'bmp', 'gif', 'svg', 'webp', 'mp4'])) {
                        $path = $savePath . $user_id . '_' . Str::random(30) . '.' . $extension;
                        Storage::disk($disk)->put($path, $content);
                        array_push($urls, $path);
                    }
                }
            }

            return $urls;
        }
    }
}
