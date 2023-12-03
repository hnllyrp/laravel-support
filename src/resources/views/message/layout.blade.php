<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title')</title>

        <!-- Styles -->
        <style>
            html, body {
                background-color: #fff;
                color: #636b6f;
                font-family: 'Nunito', sans-serif;
                font-weight: 100;
                height: 100vh;
                margin: 0;
            }

            .full-height {
                height: 100vh;
            }

            .flex-center {
                align-items: center;
                display: flex;
                justify-content: center;
            }

            .position-ref {
                position: relative;
            }

            .content {
                text-align: center;
            }


            a{text-decoration: none;}
            p{margin:0;padding:0;}
            .box{width:500px;height:100px;margin:100px auto;color:#31708f;background-color:#d9edf7;border:1px solid #bce8f1;}
            .success{color:#3c763d;background-color: #dff0d8;border-color:#d6e9c6;}
            .info{color:#31708f;background-color:#d9edf7;border-color: #bce8f1;}
            .error{color:#a94442;background-color: #f2dede;border-color:#ebccd1;}
            .msg{display: table-cell;vertical-align: middle;text-align: center;height:100px;width:500px;}
            .url a{display: block;padding: 10px;}
            .url a:hover{color:red;}
            @media screen and (max-width: 700px) {
                .box{width:100%;}
                .msg{width:100%;}
            }

        </style>
    </head>
    <body>
        <div class="flex-center position-ref full-height">
            <div class="content box">
                @yield('message')

                @yield('url')
            </div>
        </div>

        @yield('script')
    </body>
</html>
