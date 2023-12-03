<?php

namespace Hnllyrp\LaravelSupport\Rules;

use Illuminate\Contracts\Validation\Rule;

class ChineseWordRule implements Rule
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
     * 中文验证
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return preg_match('/[\x{4e00}-\x{9fa5}]+/u', $value) ? true : false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('user.chinese_word_error');
    }
}
