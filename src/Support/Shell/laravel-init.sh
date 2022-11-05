#! /bin/bash

echo "+------------------------------------------------------------------------+"
echo "|           linux下laravel安装初始化工作                                    |"
echo "+------------------------------------------------------------------------+"

# 安装composer
if [ -s /usr/local/bin/composer ]; then
    chmod a+x /usr/local/bin/composer
else
    wget https://mirrors.aliyun.com/composer/composer.phar -O /usr/local/bin/composer
fi

# 初始化 laravel 配置文件
cp .env.example .env
# 配置composer镜像并安装或更新依赖
composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/
composer u
# 配置密钥
php artisan key:generate
# 生成软链接
php artisan storage:link
# 目录权限
chmod -R 777 storage bootstrap