<?php

return [
    'key' => md5(env('APP_KEY', 'example_key')),
    'payload' => [
        'iss' => env('APP_URL', 'https://foo.com'), // 该JWT的签发者，是否使用是可选的；
        // 'aud' => '', // 接收该JWT的一方，是否使用是可选的；
        // 'sub' => '', // 该JWT所面向的用户，是否使用是可选的；
        // 'iat' => '', // issued at 在什么时候签发的(UNIX时间)，是否使用是可选的；
        // 'exp' => '', // expires 什么时候过期，这里是一个Unix时间戳，是否使用是可选的；
        // 'nbf' => '', // Not Before 如果当前时间在nbf里的时间之前，则Token不被接受；一般都会留一些余地，比如几分钟，是否使用是可选的；
        // 'jti' => '', // jwt的唯一身份标识，主要用来作为一次性token,从而回避重放攻击。
    ],
    'expires' => 30, //天数
];
