<html lang="zh-Hans">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <title>{{ config('shop.shop_name') }}</title>
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/mobile/vendor/bootstrap/css/bootstrap.min.css') }}"/>
</head>
<body>
<div class="container-fluid">
    <div class="row text-center">
        <h2> {{ $close_comment ?? '' }}</h2>
    </div>
</div>
</body>
</html>
