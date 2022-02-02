# 组件简介
基于分布式事务RT模式实现，适用于hyperf框架的组件。

## 如何使用
在hyperf框架里，把门面Db换成RT就能实现分布式事务。
```php
use Hyperf\DbConnection\Db;
use Windawake\HyperfResetTransaction\Facades\RT;

Db::beginTransaction();
...
Db::commit();

#换成
RT::beginTransaction();
...
RT::commit();
```

## 快速预览
第一步，在hyperf框架根目录下安装composer组件
```shell
## 必须使用composer2版本
composer require windawake/hyperf-reset-transaction dev-master
```
第二步，在`./config/autoload/server.php`文件，默认使用9501端口配置，然后增加9502，9503端口的配置
```php
    'servers' => [
        [
            'name' => 'http',
            'type' => Server::SERVER_HTTP,
            'host' => '0.0.0.0',
            'port' => 9501,
            'sock_type' => SWOOLE_SOCK_TCP,
            'callbacks' => [
                Event::ON_REQUEST => [Hyperf\HttpServer\Server::class, 'onRequest'],
            ],
        ],
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
        [
            'name' => 'http',
            'type' => Server::SERVER_HTTP,
            'host' => '0.0.0.0',
            'port' => 9503,
            'sock_type' => SWOOLE_SOCK_TCP,
            'callbacks' => [
                Event::ON_REQUEST => [Hyperf\HttpServer\Server::class, 'onRequest'],
            ],
        ]
    ],
```
第三步，删除`runtime`文件夹，然后创建order，storage，account3个mysql数据库实例，3个控制器，3个model，在phpunit.xml增加testsuite Transaction，然后启动web服务器。这些操作只需要执行下面命令全部完成
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

## 测试报告

本地电脑：i5-9400F 24G内存 wsl ubuntu

压测场景：

1）创建一个简单的订单 1000请求 100并发 

2）订单服务创建一个订单，然后库存服务扣减库存，最后账户服务扣减金额  1000请求 100并发

报告总结：

1）使用RT模式，创建一个订单的消耗性能跟普通事务创建一个订单+10条简单sql语句差不多

2）一个完整的创建订单是包含订单服务，库存服务和账户服务。使用RT模式，qps从140下降到40，性能大约是不使用分布式事务的1/3


**压测创建一个订单 + 10条简单订单查询**
加10条简单的sql语句，是因为RT分布式事务，后面处理的逻辑大约有10条sql，这样方便比较。

```shell
root@DESKTOP-VQOELJ5:/web/linux/php/hyperf/hyperf22# composer test -- --testsuite=Transaction --filter=testBatchCreate01

Completed 100 requests
Completed 200 requests
Completed 300 requests
Completed 400 requests
Completed 500 requests
Completed 600 requests
Completed 700 requests
Completed 800 requests
Completed 900 requests
Completed 1000 requests
Finished 1000 requests
.                                                                   1 / 1 (100%)
This is ApacheBench, Version 2.3 <$Revision: 1843412 $>
Copyright 1996 Adam Twiss, Zeus Technology Ltd, http://www.zeustech.net/
Licensed to The Apache Software Foundation, http://www.apache.org/

Benchmarking 127.0.0.1 (be patient)


Server Software:        Hyperf
Server Hostname:        127.0.0.1
Server Port:            9501

Document Path:          /api/resetOrderTest/orderWithLocal
Document Length:        10 bytes

Concurrency Level:      100
Time taken for tests:   7.408 seconds
Complete requests:      1000
Failed requests:        0
Total transferred:      153000 bytes
Total body sent:        161000
HTML transferred:       10000 bytes
Requests per second:    134.99 [#/sec] (mean)
Time per request:       740.782 [ms] (mean)
Time per request:       7.408 [ms] (mean, across all concurrent requests)
Transfer rate:          20.17 [Kbytes/sec] received
                        21.22 kb/s sent
                        41.39 kb/s total

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        0    0   0.5      0       2
Processing:    30  713  88.2    730     928
Waiting:       28  713  88.2    730     928
Total:         30  713  87.9    730     929

Percentage of the requests served within a certain time (ms)
  50%    730
  66%    735
  75%    737
  80%    739
  90%    744
  95%    750
  98%    787
  99%    862
 100%    929 (longest request)


Time: 00:07.444, Memory: 16.00 MB

OK (1 test, 2 assertions)
```

**压测开启RT分布式事务创建一个订单**
```
root@DESKTOP-VQOELJ5:/web/linux/php/hyperf/hyperf22# composer test -- --testsuite=Transaction --filter=testBatchCreate02

Completed 100 requests
Completed 200 requests
Completed 300 requests
Completed 400 requests
Completed 500 requests
Completed 600 requests
Completed 700 requests
Completed 800 requests
Completed 900 requests
Completed 1000 requests
Finished 1000 requests
.                                                                   1 / 1 (100%)
This is ApacheBench, Version 2.3 <$Revision: 1843412 $>
Copyright 1996 Adam Twiss, Zeus Technology Ltd, http://www.zeustech.net/
Licensed to The Apache Software Foundation, http://www.apache.org/

Benchmarking 127.0.0.1 (be patient)


Server Software:        Hyperf
Server Hostname:        127.0.0.1
Server Port:            9501

Document Path:          /api/resetOrderTest/orderWithRt
Document Length:        10 bytes

Concurrency Level:      100
Time taken for tests:   9.307 seconds
Complete requests:      1000
Failed requests:        0
Total transferred:      153000 bytes
Total body sent:        158000
HTML transferred:       10000 bytes
Requests per second:    107.45 [#/sec] (mean)
Time per request:       930.701 [ms] (mean)
Time per request:       9.307 [ms] (mean, across all concurrent requests)
Transfer rate:          16.05 [Kbytes/sec] received
                        16.58 kb/s sent
                        32.63 kb/s total

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        0    0   0.6      0       3
Processing:    55  910 224.4    905    1552
Waiting:       52  910 224.5    905    1552
Total:         55  910 224.4    905    1552

Percentage of the requests served within a certain time (ms)
  50%    905
  66%   1007
  75%   1070
  80%   1106
  90%   1226
  95%   1286
  98%   1336
  99%   1383
 100%   1552 (longest request)


Time: 00:09.340, Memory: 16.00 MB

OK (1 test, 1 assertion)
```

**压测创建一个完整的订单**

```shell
root@DESKTOP-VQOELJ5:/web/linux/php/hyperf/hyperf22# composer test -- --testsuite=Transaction --filter=testBatchCreate05

Completed 100 requests
Completed 200 requests
Completed 300 requests
Completed 400 requests
Completed 500 requests
Completed 600 requests
Completed 700 requests
Completed 800 requests
Completed 900 requests
Completed 1000 requests
Finished 1000 requests
F                                                                   1 / 1 (100%)
This is ApacheBench, Version 2.3 <$Revision: 1843412 $>
Copyright 1996 Adam Twiss, Zeus Technology Ltd, http://www.zeustech.net/
Licensed to The Apache Software Foundation, http://www.apache.org/

Benchmarking 127.0.0.1 (be patient)


Server Software:        Hyperf
Server Hostname:        127.0.0.1
Server Port:            9501

Document Path:          /api/resetAccountTest/createOrderWithLocal
Document Length:        25 bytes

Concurrency Level:      100
Time taken for tests:   7.061 seconds
Complete requests:      1000
Failed requests:        0
Total transferred:      168000 bytes
Total body sent:        169000
HTML transferred:       25000 bytes
Requests per second:    141.63 [#/sec] (mean)
Time per request:       706.078 [ms] (mean)
Time per request:       7.061 [ms] (mean, across all concurrent requests)
Transfer rate:          23.24 [Kbytes/sec] received
                        23.37 kb/s sent
                        46.61 kb/s total

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        0    1   3.4      0      12
Processing:    40  677  84.2    680    1036
Waiting:       38  677  84.2    680    1036
Total:         40  678  84.1    680    1048

Percentage of the requests served within a certain time (ms)
  50%    680
  66%    700
  75%    704
  80%    708
  90%    721
  95%    752
  98%    923
  99%    986
 100%   1048 (longest request)


Time: 00:07.207, Memory: 16.00 MB
```

**压测开启RT模式创建一个完整的订单**
```
root@DESKTOP-VQOELJ5:/web/linux/php/hyperf/hyperf22# composer test -- --testsuite=Transaction --filter=testBatchCreate06

Completed 100 requests
Completed 200 requests
Completed 300 requests
Completed 400 requests
Completed 500 requests
Completed 600 requests
Completed 700 requests
Completed 800 requests
Completed 900 requests
Completed 1000 requests
Finished 1000 requests
F                                                                   1 / 1 (100%)
This is ApacheBench, Version 2.3 <$Revision: 1843412 $>
Copyright 1996 Adam Twiss, Zeus Technology Ltd, http://www.zeustech.net/
Licensed to The Apache Software Foundation, http://www.apache.org/

Benchmarking 127.0.0.1 (be patient)


Server Software:        Hyperf
Server Hostname:        127.0.0.1
Server Port:            9501

Document Path:          /api/resetAccountTest/createOrderWithRt
Document Length:        25 bytes

Concurrency Level:      100
Time taken for tests:   24.382 seconds
Complete requests:      1000
Failed requests:        0
Total transferred:      168000 bytes
Total body sent:        166000
HTML transferred:       25000 bytes
Requests per second:    41.01 [#/sec] (mean)
Time per request:       2438.191 [ms] (mean)
Time per request:       24.382 [ms] (mean, across all concurrent requests)
Transfer rate:          6.73 [Kbytes/sec] received
                        6.65 kb/s sent
                        13.38 kb/s total

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        0    0   0.4      0       2
Processing:   133 2350 222.8   2394    3058
Waiting:      131 2350 222.8   2394    3058
Total:        133 2350 222.6   2394    3059

Percentage of the requests served within a certain time (ms)
  50%   2394
  66%   2410
  75%   2419
  80%   2426
  90%   2446
  95%   2469
  98%   2696
  99%   2881
 100%   3059 (longest request)


Time: 00:24.478, Memory: 16.00 MB
```

## 参考教程
https://learnku.com/articles/62377


![](https://cdn.learnku.com/uploads/images/202201/31/46914/7PISKMj6cY.jpg!large)

扫码进微信群。希望有更多的朋友相互学习和一起研究分布式事务的知识。

