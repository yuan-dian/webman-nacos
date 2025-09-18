# webman-nacos 插件

# 简介
Webman-naocs是基于PHP开发的Webman插件生态下的Nacos客户端；

灵感来自于workbunny/webman-nacos、hyperf/config-nacos，其中的一些配置与实现都是源于其项目！


# 安装

``` composer require yuandian/webman-nacos ```

# 依赖
如果需要配置格式是yaml，需要安装yaml扩展或者symfony/yaml库
- 安装yaml：```pecl install yaml``` 【其他方式自行处理】
- 安装symfony/yaml：```composer require symfony/yaml```

# 特性
- 支持配置获取
- 支持实例注册
- 支持通过注解自动注入配置
- 配置变更通过webman/channel进程间通信

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

## 捐献

![](./wechat.png)
![](./alipay.png)