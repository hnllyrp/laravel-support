<?php

namespace Hnllyrp\LaravelSupport\Rules;

use Illuminate\Contracts\Validation\Rule;

class UserName implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * 用户姓名 验证（支持手机号或邮箱或中文、英文字母数字（2-16位））
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // 手机号
        if (preg_match('/^(1[3-9])\d{9}$/', $value)) {
            return true;
        }

        // 邮箱
        if (preg_match('/^[a-zA-Z0-9]+([-_.][a-zA-Z0-9]+)*@([a-zA-Z0-9]+[-.])+([a-z]{2,5})$/', $value)) {
            return true;
        }

        // 中文或英文或字母或数字、下划线（2-16位）
        return preg_match('/^[\x{4e00}-\x{9fa5}A-Za-z0-9_]{2,16}$/u', $value) ? true : false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('user.user_name_error');
    }
}
