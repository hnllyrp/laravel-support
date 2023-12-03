<?php


namespace Hnllyrp\LaravelSupport\Support\Traits;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Hnllyrp\LaravelSupport\Exceptions\HttpException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;

trait HttpRequest
{
    /**
     * Make a get request.
     *
     * @param string $uri
     * @param array $query
     * @param array $config
     * @return ResponseInterface|array|string
     */
    public static function get($uri = '', $query = [], $config = [])
    {
        try {
            $response = self::request('get', $uri, $query, $config, 'query');
        } catch (HttpException $e) {
            return false;
        }

        return self::unwrapResponse($response);
    }

    /**
     * Make a post request.
     *
     * @param string $uri
     * @param array $params
     * @param array $config
     * @return ResponseInterface|array|string
     */
    public static function post($uri = '', $params = [], $config = [])
    {
        try {
            $response = self::request('post', $uri, $params, $config, 'form_params');
        } catch (HttpException $e) {
            return false;
        }

        return self::unwrapResponse($response);
    }

    /**
     * Make a post request with json params.
     *
     * @param string $uri
     * @param array $params
     * @param array $config
     * @return ResponseInterface|array|string
     */
    public static function postJson($uri = '', $params = [], $config = [])
    {
        try {
            $response = self::request('post', $uri, $params, $config, 'json');
        } catch (HttpException $e) {
            return false;
        }

        return self::unwrapResponse($response);
    }

    /**
     * Http 请求 GET/POST
     *
     * @link https://guzzle-cn.readthedocs.io/zh-cn/latest/quickstart.html#id2
     *
     * @param string $method
     * @param string $uri
     * @param array $params
     * @param array $config
     * @param string $contentType
     * @return \Psr\Http\Message\ResponseInterface
     * @throws HttpException
     */
    public static function request($method = 'GET', $uri = '', $params = [], $config = [], $contentType = 'form_params')
    {
        $config['verify'] = Arr::get($config, 'verify', false);; // 跳过证书检查
        $config['timeout'] = Arr::get($config, 'timeout', 5); // 超时时间

        $client = new Client($config);

        $method = strtoupper($method);
        $options = $method == 'GET' ? ['query' => $params] : [$contentType => $params];

        try {
            $response = $client->request($method, $uri, $options);

        } catch (RequestException $exception) {

            // 综合一个通用返回request response处理的方式
            $request = $exception->getRequest();
            if ($exception->hasResponse()) {
                $response = $exception->getResponse();
                // 错误消息主体 body
                $body = (string)$response->getBody()->getContents(); // Body, normally it is JSON;
            }

            $message = $body ?? $exception->getMessage();

            // 调试模式
            if (config('app.debug')) {
                $request_arr = \GuzzleHttp\Psr7\Message::parseMessage(\GuzzleHttp\Psr7\Message::toString($request));
                Log::debug('request', $request_arr);
                $response_arr = \GuzzleHttp\Psr7\Message::parseMessage(\GuzzleHttp\Psr7\Message::toString($response ?? null));
                Log::debug('response', $response_arr);
            } else {
                // 简单返回错误消息
                Log::debug('params:', $params); // 记录原始请求参数信息
                Log::debug('errors：' . $exception->getCode() . $message);
            }

            throw new HttpException($message, $exception->getCode());

        } catch (\Throwable $throwable) {
            //other errors
            Log::debug('errors：' . $throwable->getMessage());
            throw new HttpException($throwable->getMessage(), $throwable->getCode());
        }

        return $response;
    }

    /**
     * 文件上传 请求
     *
     * @link https://guzzle-cn.readthedocs.io/zh-cn/latest/quickstart.html#id9
     *
     * @param string $method
     * @param string $uri
     * @param array $options
     * @param array $config
     * @return \Psr\Http\Message\ResponseInterface
     * @throws HttpException
     */
    public static function fileUpload($method = 'POST', $uri = '', $options = [], $config = [])
    {
        $config['timeout'] = Arr::get($config, 'timeout', 5); // 超时时间

        $client = new Client($config);

        try {
            $response = $client->request($method, $uri, $options);

        } catch (RequestException $exception) {
            $errorCode = $exception->getCode();
            if ($exception->hasResponse()) {
                $response = $exception->getResponse();
                // 错误消息主体 body
                $body = (string)$response->getBody()->getContents(); // Body, normally it is JSON;
            }

            $message = $body ?? $exception->getMessage();

            if (config('app.debug')) {
                Log::debug('errors：' . $errorCode . $message);
            }

            throw new HttpException($message, $errorCode);
        }

        return $response;
    }

    /**
     * 并发请求
     *
     * @param string $base_uri
     * @param array $sub_uri
     * @return array
     * @throws \Throwable
     */
    public static function concurrency($base_uri = '', $sub_uri = [])
    {
        $client = self::getClient(['base_uri' => $base_uri]);

        $promises = [];
        foreach ($sub_uri as $uri) {
            $promises[$uri] = $client->getAsync($uri); // 并发请求
        }

        // Wait for the requests to complete; throws a ConnectException
        // if any of the requests fail
        $responses = \GuzzleHttp\Promise\Utils::unwrap($promises);

        return $responses;
    }

    /**
     * Return http client.
     *
     * @link https://docs.guzzlephp.org/en/stable/quickstart.html
     *
     * @param array $options
     * @return \GuzzleHttp\Client
     */
    protected static function getClient(array $options = [])
    {
        return new Client($options);
    }

    /**
     * Convert response contents to json.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return ResponseInterface|array|string
     */
    protected static function unwrapResponse(ResponseInterface $response)
    {
        $contentType = $response->getHeaderLine('Content-Type');
        $contents = $response->getBody()->getContents();

        if (false !== stripos($contentType, 'json') || stripos($contentType, 'javascript')) {
            return json_decode($contents, true);
        } elseif (false !== stripos($contentType, 'xml')) {
            return json_decode(json_encode(simplexml_load_string($contents)), true);
        }

        return $contents;
    }
}
