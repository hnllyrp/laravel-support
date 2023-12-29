<?php

namespace Hnllyrp\LaravelSupport\Support\Traits;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Hnllyrp\LaravelSupport\Exceptions\HttpException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;

trait HttpRequest
{
    protected static $httpClient;

    /**
     * head 请求 测试超链接的有效性、可用性
     * @param string $uri
     * @return bool
     */
    public static function head($uri = '')
    {
        $client = static::getClient(['timeout' => 3]);
        try {
            $response = $client->head($uri);
        } catch (GuzzleException $e) {
            Log::error($e->getMessage());
            return false;
        }

        if ($response->getStatusCode() == 200) {
            return true;
        }
        return false;
    }

    /**
     * Make a get request.
     *
     * @param string $uri
     * @param array $query
     * @param array $config
     * @return ResponseInterface|array|string
     * @throws HttpException
     */
    public static function get($uri = '', $query = [], $config = [])
    {
        try {
            $response = static::request('get', $uri, $query, $config, 'query');
        } catch (HttpException $e) {
            throw $e;
        }

        return static::unwrapResponse($response);
    }

    /**
     * Make a post request.
     *
     * @param string $uri
     * @param array $params
     * @param array $config
     * @return ResponseInterface|array|string
     * @throws HttpException
     */
    public static function post($uri = '', $params = [], $config = [])
    {
        try {
            $response = static::request('post', $uri, $params, $config);
        } catch (HttpException $e) {
            throw $e;
        }

        return static::unwrapResponse($response);
    }

    /**
     * Make a post request with json params.
     *
     * @param string $uri
     * @param array $params
     * @param array $config
     * @return ResponseInterface|array|string
     * @throws HttpException
     */
    public static function postJson($uri = '', $params = [], $config = [])
    {
        try {
            $response = static::request('post', $uri, $params, $config, 'json');
        } catch (HttpException $e) {
            throw $e;
        }

        return static::unwrapResponse($response);
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
        $client = static::getClient(['base_uri' => $base_uri]);

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
    public static function getClient(array $options = [])
    {
        if (is_null(self::$httpClient)) {
            self::$httpClient = new Client($options);
        }
        return self::$httpClient;
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
            return json_decode(json_encode(simplexml_load_string($contents, 'SimpleXMLElement', LIBXML_NOCDATA), JSON_UNESCAPED_UNICODE), true);
        }

        return $contents;
    }
}
