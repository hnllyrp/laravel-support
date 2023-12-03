<?php

namespace Hnllyrp\LaravelSupport\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Class Etag
 * https://github.com/kbdxbt/kbframe-common/blob/main/Http/Middleware/ETag.php
 */
class Etag
{
    /**
     * Implement Etag support.
     *  实体标签（Entity Tag）：一种HTTP协议中的响应头字段，用于确定客户端缓存的资源是否与服务器上的资源相同。它是一个唯一的字符串，表示资源的版本，当资源发生变化时，ETag会发生变化。
     * @param \Illuminate\Http\Request $request the HTTP request
     * @param \Closure $next closure for the response
     */
    public function handle(Request $request, Closure $next)
    {
        // If this was not a get or head request, just return
        if (!$request->isMethod('get') && !$request->isMethod('head')) {
            return $next($request);
        }

        // Get the initial method sent by client
        $initialMethod = $request->method();

        // Force to get in order to receive content
        $request->setMethod('get');

        // Get response
        $response = $next($request);

        // Generate Etag
        $etag = md5(json_encode($response->headers->get('origin')) . $response->getContent());

        // Load the Etag sent by client
        $requestEtag = str_replace('"', '', $request->getETags());

        // Check to see if Etag has changed
        if ($requestEtag && $requestEtag[0] === $etag) {
            $response->setNotModified();
        }

        // Set Etag
        $response->setEtag($etag);

        // Set back to original method
        $request->setMethod($initialMethod); // set back to original method

        // Send response
        return $response;
    }
}
