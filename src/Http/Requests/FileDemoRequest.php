<?php

namespace Hnllyrp\LaravelSupport\Http\Requests;

use Hnllyrp\LaravelSupport\Exceptions\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\File;


class FileDemoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            /**
             * file 字段
             * 1. 验证的字段必须是成功上传的文件。
             * 2. 验证的文件必须是图片 (jpeg, png, bmp, gif, svg, or webp)
             * 3. 验证文件的大小最大值 2M = 2048Kb
             */
            'file' => 'required|file|image|max:2048',
            /**
             * picture 字段
             * 1. 验证的文件必须具有与列出的其中一个扩展名相对应的 MIME 类型。
             */
            'picture' => 'required|image|mimes:jpeg,bmp,png,gif|max:1024',
            /**
             * video 字段
             * 验证的文件必须具备与列出的其中一个扩展相匹配的 MIME 类型
             */
            'video' => 'required|mimetypes:video/avi,video/mpeg,video/quicktime'
        ];
    }

    public function imageRules()
    {
        return [
            'file' => ['required', File::image()->max(20480)],
        ];
    }

    public function videoRules()
    {
        return [
            'file' => [
                'required',
                File::types(['flv', 'mp4', 'm3u8', 'ts', '3gp', 'mov', 'avi', 'wmv'])->max(20480),
            ],
        ];
    }

    public function excelRules()
    {
        return [
            'file' => [
                'required',
                File::types(['doc', 'xlsx', 'xls', 'docx', 'ppt', 'odt', 'ods', 'odp'])->max(20480),
            ],
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     *
     * @return array
     */
    public function messages()
    {
        return [
            'file.required' => trans('file.required'),
            'file.image' => trans('file.image'),
            'file.mimes' => '上传文件类型错误',
            'file.max' => '文件类型不能超过20M',

            'picture.required' => '请选择要上传的图片',
            'picture.image' => '只支持上传图片',
            'picture.mimes' => '只支持上传jpg/png/jpeg格式图片',
            'picture.max' => '上传图片超过最大尺寸限制(1M)'
        ];
    }

    /**
     * 获取验证错误的自定义属性。
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'file' => '文件',
            'picture' => '图片'
        ];
    }

    /**
     * 验证失败自定义跳转
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @throws ValidationException
     */
    public function failedValidation(Validator $validator)
    {
        // 默认返回上一页
        $redirectUrl = $this->redirector->back();

        throw (new ValidationException($validator))
            ->status(2)
            ->redirectTo($redirectUrl);
    }
}
