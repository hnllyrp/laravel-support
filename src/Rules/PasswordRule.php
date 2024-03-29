<?php

namespace Hnllyrp\LaravelSupport\Rules;

use Illuminate\Contracts\Validation\Rule;

class PasswordRule implements Rule
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
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // 正则：8-16位且必须包含大小写字母和数字的组合
        $reg = '/^(?=.*\\d)(?=.*[a-z])(?=.*[A-Z]).{8,16}$/';

        // 正则： 必须以字母开头，长度在6-18之间，只能包含字符、数字和下划线
        $reg2 = '/^[a-zA-Z]\w{5,17}$/';

        return (bool)preg_match($reg, $value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('user.user_pass_limit');
    }
}
