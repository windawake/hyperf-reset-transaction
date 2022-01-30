# 组件简介
基于分布式事务RT模式实现，适用于hyperf框架的组件。

## 快速预览
```shell
## 必须使用composer2版本
composer require windawake/hyperf-reset-transaction dev-master
```

首先删除`runtime`文件夹，然后创建order，storage，account3个mysql数据库实例，3个控制器，3个model，在phpunit.xml增加testsuite Transaction，然后启动web服务器。这些操作只需要执行下面命令全部完成
```shell
rm -rf ./runtime && php ./bin/hyperf.php resetTransact:create-examples && php ./bin/hyperf.php start
```

最后运行测试脚本 `
composer test -- --testsuite=Transaction --filter=ServiceTest
`运行结果如下所示，3个例子测试通过。
```shell
DESKTOP:/web/linux/php/hyperf/hyperf22# composer test -- --testsuite=Transaction --filter=ServiceTest
Time: 00:00.596, Memory: 18.00 MB

OK (3 tests, 12 assertions)
```

## 功能特性
1. 开箱即用，不需要重构原有项目的代码，与mysql事务写法一致，简单易用。
2. 两段提交的强一致性事务，高并发下，支持读已提交的事务隔离级别，数据一致性100%接近mysql xa。
3. 性能超过seata AT模式，由于事务拆分成多个，变成了几个小事务，压测发现比mysql普通事务更少发生死锁。
4. 支持分布式事务嵌套，与savepoint一致效果。
5. 支持避免不同业务代码并发造成脏数据的问题。
6. 默认支持http协议的服务化接口，想要支持其它协议则需要重写中间件。
7. [支持子服务嵌套分布式事务（全球首创）](#支持子服务嵌套分布式事务（全球首创）)。
8. 支持服务，本地事务和分布式事务混合嵌套（全球首创）
9. 支持超时3次重试，重复请求保证幂等性
10. 支持go，java语言（开发中）

对比阿里seata AT模式，有什么优点？请阅读 https://learnku.com/articles/63797

## 参考教程
https://learnku.com/articles/62377

