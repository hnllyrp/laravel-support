# laravel-support

- 基于 laravel 提供扩展支持, 主要版本6.x


## 目录结构
```shell
├─config  配置文件
├─Console 命令行
├─Mail 邮件
├─Middleware 中间件
├─Providers 服务提供者
├─resources 静态资源 语言包、视图等
├─Rules 验证规则
├─Services 服务类
├─Support
│  ├─Abstracts
│  │  ├─Service.php
│  ├─Shell
│  │  ├─laravel-init.sh laravel初始化工作

│  ├─Arr.php 常用数组函数

```


## 功能列表
- 自定义服务提供者类（模块化、插件化）
  
- jwt token 服务类
  
- 常用表单验证规则类
  - 用户名、手机号、密码、身份证号码、银行卡、

- 中间件
  - 跨域中间件
  - 防止表单重复提交
  - jwt token 验证中间件
    
  - 记录请求响应中间件

- 命令行
  - env配置文件编辑
  - curd代码生成器
  - 基于现有数据库生成Model
