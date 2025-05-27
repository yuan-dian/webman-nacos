# webman-nacos 插件

# 简介
Webman-naocs是基于PHP开发的Webman插件生态下的Nacos客户端；

灵感来自于workbunny/webman-nacos，其中的一些配置与实现都是源于其项目，这里对 workbunny 表示感谢！

与其区别在于配置监听，支持通过注解实现配置类的自动注入，取消了写入本地文件的方式

实例注册基本是复用workbunny/webman-nacos的实现

# 安装

``` composer require yuandian/webman-nacos ```

# 特性
- 支持配置获取
- 支持实例注册
- 支持通过注解自动注入配置

# 使用

- 自动注入配置类
```php
namespace app\config;

use yuandian\WebmanNacos\Annotation\NacosConfiguration;

#[NacosConfiguration("datasource")]
class Config
{
    #[NacosValue('host', '')] // 可以使用NacosValue设置别名与默认值
    public string $url;
    public string $username;
    public string $password;
}
``` 

- 获取配置
```php
$config =  \yuandian\Container\Container::getInstance()->get(Config::class);
``` 
- 不通过配置类直接获取配置
```php
$value = ConfigManage::getConfig('aa.bb', '');
``` 

## 捐献

![](./wechat.png)
![](./alipay.png)