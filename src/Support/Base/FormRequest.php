<?php

namespace Hnllyrp\LaravelSupport\Support\Base;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest as Request;

/**
 * Class FormRequest
 * 参考：https://zhuanlan.zhihu.com/p/477107031
 */
class FormRequest extends Request
{
    /**
     * 定义验证规则
     *
     * @return array
     */
    public function rules()
    {
        $method = $this->route()->getActionMethod() . 'Rules';

        if (method_exists($this, $method)) {
            return $this->$method();
        }

        return $this->defaultRules();
    }


    /**
     * 默认 验证规则
     * @return array
     */
    protected function defaultRules()
    {
        return [];
    }

    /**
     * Handle a failed validation attempt. 验证失败自定义跳转
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function failedValidation(Validator $validator)
    {
        // 如果是 json 请求
        if ($this->expectsJson() || $this->isJson()) {
            // 抛 HttpResponseException 自定义 json 消息异常（不用手动捕获 laravel会自动捕获）
            $error = $validator->errors()->first();

            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                // response()->json(['code' => 422, 'status' => 'fail', 'msg' => $error], 422)
                response()->json(['code' => 1, 'status' => 'fail', 'msg' => $error], 200)
            );
        }

        // 默认返回上一页
        $redirectUrl = $this->redirector->back(); // $redirectUrl = $this->getRedirectUrl();

        throw (new \Illuminate\Validation\ValidationException($validator))
            ->status(200)
            ->errorBag($this->errorBag)
            ->redirectTo($redirectUrl);
    }

}
