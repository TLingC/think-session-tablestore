# think-session-tablestore
[![](https://img.shields.io/packagist/v/tlingc/think-session-tablestore.svg)](https://packagist.org/packages/tlingc/think-session-tablestore)
[![](https://img.shields.io/packagist/dt/tlingc/think-session-tablestore.svg)](https://packagist.org/packages/tlingc/think-session-tablestore)
[![](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE.md)

ThinkPHP 6 阿里云表格存储（OTS）Session驱动

## 安装

```
composer require tlingc/think-session-tablestore
```

## 配置
在阿里云表格存储控制台新增实例及数据表，表主键名称为`key`、类型为`字符串`，数据生命周期根据实际缓存需求设置，最大版本数设置为`1`。

config/session.php
```php
// 驱动方式
'type' => \tlingc\think\session\driver\Tablestore::class,
// 过期时间
'expire' => 3600,
// 前缀
'prefix' => '',
// 表格存储实例访问地址
'endpoint' => '',
// 阿里云access key id
'access_key_id' => '',
// 阿里云access key secret
'access_key_secret' => '',
// 表格存储实例名称
'instance_name' => '',
// 表格存储数据表名
'table_name' => '',
// 启用数据压缩
'data_compress' => true
```

## 限制
1. 数据过期机制仅在数据被取出及超过生命周期时应用，不建议使用此驱动缓存永久数据。
2. 关于表格存储产品自身限制，参阅 [https://help.aliyun.com/document_detail/91524.html](https://help.aliyun.com/document_detail/91524.html)。

## 协议
MIT