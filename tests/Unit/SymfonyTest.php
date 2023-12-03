<?php

namespace Tests\Unit;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Class SymfonyTest
 * @example vendor/bin/phpunit tests/Unit/SymfonyTest
 * @package Tests\Unit
 */
class SymfonyTest extends TestCase
{
    /**
     * testRequest
     *
     * @return void
     */
    public function testRequest()
    {
        dump(__FUNCTION__);

        // 创建一个 request 获取请求信息
        // $request = Request::createFromGlobals();
        //
        // // 添加 query 参数
        // $request->query->add(['query'=> 1]);
        // dump($request->query->all());
        //
        // // 添加 attributes 参数
        // $request->attributes->add(['attributes'=> 1]);
        // $request->attributes->get('attributes') ?? null;
        // dump($request->attributes->all());
        //
        // // filter
        // echo $request->query->filter('user_id', null, FILTER_VALIDATE_INT);
        //
        // $content = $request->getContent();
        // dump($content);


        // 模拟一个请求 发起请求
        $symfonyRequest = Request::create(
            'http://api.pet.test/api/developer/test',
            'GET',
            array('name' => 'Fabien')
        );

        dd('');

        // $response = $this->get('/');
        //
        // $response->assertStatus(200);
    }


    public function testResponse()
    {
        dump(__FUNCTION__);

        // 创建响应
        // $response = new Response(
        //     'Content',
        //     Response::HTTP_OK,
        //     array('content-type' => 'text/html')
        // );
        //
        // // 也可以在响应创建之后进行处理
        // $response->setContent('Hello World');
        //
        // // the headers public attribute is a ResponseHeaderBag
        // // 公有属性headers，是一个ResponseHeaderBag实例
        // $response->headers->set('Content-Type', 'text/plain');
        //
        // $response->setStatusCode(Response::HTTP_NOT_FOUND);
        //
        // // 默认情况下，Symfony假定你的响应是UTF-8编码
        // $response->setCharset('UTF-8');
        //
        // // 设置 cookie
        // $response->headers->setCookie(new Cookie('foo', 'bar'));
        //
        // // 设置 重定向
        // $response = new RedirectResponse('http://example.com/');

        // 发送响应
        // $response->send();

        // dump($response);

        // 创建JSON响应
        $response = new Response();
        $response->setContent(json_encode(array(
            'data' => 123,
        )));
        $response->headers->set('Content-Type', 'application/json');

        // 使用自带的JsonResponse类，可以简化
        $response = new JsonResponse();
        $response->setData(array(
            'data' => 123
        ));

        dump($response);
        dd();

    }
}
