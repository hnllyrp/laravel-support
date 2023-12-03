<?php

namespace Hnllyrp\LaravelSupport\Support\Facades;


use Illuminate\Support\Facades\Facade;
use Hnllyrp\LaravelSupport\Support\Response as FrontResponse;

/**
 * Class Response
 *
 * @method static FrontResponse success($data = null, string $message = '', array $headers = [], int $option = 0)
 * @method static FrontResponse fail(string $message = '', int $status = 500, array $header = [], int $options = 0)
 * @method static FrontResponse message(string $message = '', string $type = 'success', $redirect = null, $timer = 3, array $headers = [])
 * @method static FrontResponse noContent()
 * @method static mixed assign($key, $value = null)
 * @method static mixed|\Illuminate\View\View display($view = null, $data = [], array $mergeData = [])
 * @method static mixed|array|string fetch($view = null, $data = [], array $mergeData = [])
 *
 */
class Response extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FrontResponse::class;
    }
}
