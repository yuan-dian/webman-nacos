# AGENTS.md - Webman-Nacos 开发指南

## 项目概述
这是一个 PHP 库（webman-nacos 插件），用于在 Webman 框架中集成 Nacos 配置中心。

## 构建和测试命令

### 依赖安装
```bash
composer install
```

### 代码检查
```bash
# 检查 PHP 语法
find src -name "*.php" -exec php -l {} \;

# 运行代码风格检查（需要安装 php-cs-fixer）
# composer require --dev friendsofphp/php-cs-fixer
# vendor/bin/php-cs-fixer fix --dry-run

# 静态分析（需要安装 phpstan）
# composer require --dev phpstan/phpstan
# vendor/bin/phpstan analyse src
```

### 测试
```bash
# 运行所有测试（需要安装 phpunit）
# composer require --dev phpunit/phpunit
# vendor/bin/phpunit

# 运行单个测试文件
# vendor/bin/phpunit tests/ExampleTest.php

# 运行特定测试方法
# vendor/bin/phpunit --filter testMethodName
```

## 代码风格指南

### 文件结构
- 每个 PHP 文件以 `<?php` 开头
- 使用 `declare(strict_types=1);` 声明严格类型
- 文件头注释包含版权信息和作者信息
- 命名空间与目录结构一致（PSR-4）

### 命名约定
- **类名**：PascalCase（如 `NacosClient`、`ConfigProvider`）
- **方法名**：camelCase（如 `getAccessToken`、`parseYaml`）
- **常量**：UPPER_SNAKE_CASE（如 `WORD_SEPARATOR`、`LINE_SEPARATOR`）
- **变量**：camelCase（如 `$configData`、`$serviceName`）
- **私有属性**：camelCase（如 `$cacheMd5`）

### 类型声明
- 使用 PHP 8.1+ 类型系统
- 方法参数和返回值必须声明类型
- 使用联合类型（如 `string|UriInterface`）
- 使用可空类型（如 `?string`）
- 使用数组形状注解（`#[ArrayShape]`）

### 导入规范
- 每行一个 `use` 语句
- 按字母顺序排列导入
- 优先使用完全限定类名（如 `\RuntimeException`）
- 避免使用别名，除非有命名冲突

### 错误处理
- 使用具体的异常类型（`\InvalidArgumentException`、`\RuntimeException`）
- 异常消息应清晰描述问题
- 使用 `try-catch` 捕获可恢复的异常
- 记录错误日志（使用 `support\Log`）

### 注释规范
- 使用 PHPDoc 格式注释
- 为公共方法添加完整文档块
- 包含 `@param`、`@return`、`@throws` 标签
- 使用中文注释描述业务逻辑

### 代码组织
- 相关功能组织在同一命名空间下
- 使用 trait 复用代码（如 `AccessToken`）
- 抽象类提供通用实现（如 `AbstractProvider`）
- 版本特定实现放在独立目录（如 `V1/`、`V2/`）

### HTTP 客户端
- 使用 GuzzleHttp 作为 HTTP 客户端
- 支持同步和异步请求
- 使用 `RequestOptions` 常量定义请求选项
- 正确处理响应状态码

### 配置管理
- 配置通过 `Config` 类管理
- 支持从 Webman 配置系统读取
- 提供 getter 方法访问配置值
- 支持默认值

### 日志记录
- 使用 `support\Log` 记录日志
- 错误级别：`Log::error()`
- 信息级别：`Log::info()`
- 日志消息使用中文描述

### 协程支持
- 使用 `Workerman\Coroutine` 创建协程
- 支持协程和异步两种模式
- 正确处理协程异常

## 开发工作流

### 添加新功能
1. 在相应的 Provider 类中添加方法
2. 确保方法签名符合现有模式
3. 添加适当的类型声明
4. 编写完整的 PHPDoc 注释
5. 测试功能是否正常工作

### 修改现有代码
1. 保持向后兼容性
2. 遵循现有代码风格
3. 更新相关文档注释
4. 测试修改是否影响其他功能

### 调试技巧
- 使用 `var_dump()` 或 `print_r()` 进行快速调试
- 检查 GuzzleHttp 响应对象
- 验证配置是否正确加载
- 查看日志文件获取错误信息

## 注意事项
- 项目要求 PHP 8.1 或更高版本
- 依赖 GuzzleHttp 7.4 或更高版本
- 支持 YAML 解析（需要 yaml 扩展或 symfony/yaml 库）
- 配置变更通过 webman/channel 进行进程间通信