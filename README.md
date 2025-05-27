# webman-nacos 插件


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