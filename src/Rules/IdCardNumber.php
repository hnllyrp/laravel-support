<?php

namespace Hnllyrp\LaravelSupport\Rules;

use Illuminate\Contracts\Validation\Rule;

class IdCardNumber implements Rule
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
     * 验证身份证号格式 (简单 15或18位含X）
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return preg_match('/^\d{15}|\d{18}$/', $value) ? true : false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('user.id_card_number_error');
    }
}
