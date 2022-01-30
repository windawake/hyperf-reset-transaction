# 组件简介
基于Hyperf框架的重置型分布式事务组件，适用于服务化之后调用http协议的api接口

## 功能特性
1. 开箱即用，不需要重构原有项目的代码，与mysql事务写法一致，简单易用。
2. 遵守两段提交协议，属于强一致性事务，高并发下，支持读已提交的事务隔离级别。
3. 由于事务拆分成多个，变成了几个小事务，压测发现比mysql普通事务更少发生死锁。
4. 支持事务嵌套，与savepoint一致效果。
5. 支持避免不同业务代码并发造成脏数据的问题。
6. 默认支持http协议的服务化接口，想要支持其它协议则需要重写中间件。

## 解决了哪些并发场景
- [x] 一个待发货订单，用户同时操作发货和取消订单，只有一个成功
- [x] 积分换取优惠券，只要出现积分不够扣减或者优惠券的库存不够扣减，就会全部失败。

## 参考教程
https://learnku.com/articles/62377


## demo

composer test -- --testsuit=Transaction

composer require doctrine/dbal

```c++
[
    'name' => 'http',
    'type' => Server::SERVER_HTTP,
    'host' => '0.0.0.0',
    'port' => 9502,
    'sock_type' => SWOOLE_SOCK_TCP,
    'callbacks' => [
        Event::ON_REQUEST => [Hyperf\HttpServer\Server::class, 'onRequest'],
    ],
],
```
