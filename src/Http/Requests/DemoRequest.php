<?php

namespace Hnllyrp\LaravelSupport\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DemoRequest extends FormRequest
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
        /**
         * 常见验证规则
         */
        return [
            // integer 整数
            'id' => 'required|integer',
            // string 字符串
            'string' => 'required|string',
            // 验证字段可能包含字母、数字，以及破折号 (-) 和下划线 ( _ )。
            'username' => 'required|alpha_dash',
            // 验证手机号在数据库 users表里唯一
            'mobile' => 'required|unique:users',
            // 验证时间 格式 例如 2013-06-19
            'start_time' => 'required|date',
            // 存在时验证不能为空 filled。例如大于0的场景
            'number' => 'filled|string',
            // 存在时验证 可为 null
            'remark' => 'nullable|string',
            // 大小在2到30之间
            'name' => 'required|between:2,30',
            // different 密码不能与name相同， confirmed 必须与确认密码一致
            'password' => 'required|different:name|confirmed',

            // required_if 如果 type=2 则必填
            'bank_no' => 'required_if:type,2',

        ];
    }

    public function messages()
    {
        return [
            'password.required' => '密码不能为空',
            'password.different' => '密码不能与name相同',
            'password.confirmed' => '两次密码不一致',
            'mobile.unique' => '手机号已被注册',
        ];
    }
}
