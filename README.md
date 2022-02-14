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

最后一步，运行测试脚本 `
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

- 压测场景：

1）创建一个简单的订单 1000请求 100并发 

2）订单服务创建一个订单，然后库存服务扣减库存，最后账户服务扣减金额  1000请求 100并发

- 报告总结：

1）使用RT模式，创建一个订单的消耗性能跟普通事务创建一个订单+7条简单sql语句差不多

2）一个完整的创建订单是包含订单服务，库存服务和账户服务。使用RT模式，qps从109下降到53，性能大约是不使用分布式事务的1/2

- 压测前准备：

做测试之前需要设置mysql最大连接数为3000：

```sql
set global max_connections=3000;
```

**压测创建一个订单 + 7条简单订单查询**

加7条简单的sql语句，是因为RT分布式事务，后面处理的逻辑大约有7条sql，这样方便比较。

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
Time taken for tests:   5.863 seconds
Complete requests:      1000
Failed requests:        0
Total transferred:      153000 bytes
Total body sent:        161000
HTML transferred:       10000 bytes
Requests per second:    170.58 [#/sec] (mean)
Time per request:       586.251 [ms] (mean)
Time per request:       5.863 [ms] (mean, across all concurrent requests)
Transfer rate:          25.49 [Kbytes/sec] received
                        26.82 kb/s sent
                        52.31 kb/s total

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        0    0   0.4      0       2
Processing:    26  562  77.5    582     682
Waiting:       24  562  77.5    581     682
Total:         26  563  77.2    582     683

Percentage of the requests served within a certain time (ms)
  50%    582
  66%    585
  75%    588
  80%    590
  90%    593
  95%    596
  98%    601
  99%    626
 100%    683 (longest request)


Time: 00:05.894, Memory: 16.00 MB

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
Time taken for tests:   6.006 seconds
Complete requests:      1000
Failed requests:        0
Total transferred:      153000 bytes
Total body sent:        158000
HTML transferred:       10000 bytes
Requests per second:    166.51 [#/sec] (mean)
Time per request:       600.557 [ms] (mean)
Time per request:       6.006 [ms] (mean, across all concurrent requests)
Transfer rate:          24.88 [Kbytes/sec] received
                        25.69 kb/s sent
                        50.57 kb/s total

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        0    0   0.6      0       3
Processing:    55  579  58.7    578     775
Waiting:       52  579  58.7    578     775
Total:         55  579  58.7    578     777

Percentage of the requests served within a certain time (ms)
  50%    578
  66%    597
  75%    610
  80%    619
  90%    641
  95%    672
  98%    703
  99%    753
 100%    777 (longest request)


Time: 00:06.036, Memory: 16.00 MB

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
.                                                                   1 / 1 (100%)
This is ApacheBench, Version 2.3 <$Revision: 1843412 $>
Copyright 1996 Adam Twiss, Zeus Technology Ltd, http://www.zeustech.net/
Licensed to The Apache Software Foundation, http://www.apache.org/

Benchmarking 127.0.0.1 (be patient)


Server Software:        Hyperf
Server Hostname:        127.0.0.1
Server Port:            9501

Document Path:          /api/resetAccountTest/createOrderWithLocal
Document Length:        24 bytes

Concurrency Level:      100
Time taken for tests:   9.143 seconds
Complete requests:      1000
Failed requests:        0
Total transferred:      167000 bytes
Total body sent:        169000
HTML transferred:       24000 bytes
Requests per second:    109.37 [#/sec] (mean)
Time per request:       914.296 [ms] (mean)
Time per request:       9.143 [ms] (mean, across all concurrent requests)
Transfer rate:          17.84 [Kbytes/sec] received
                        18.05 kb/s sent
                        35.89 kb/s total

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        0    0   0.5      0       2
Processing:    62  881 120.0    879    1448
Waiting:       60  881 120.0    879    1448
Total:         62  881 120.1    879    1449

Percentage of the requests served within a certain time (ms)
  50%    879
  66%    897
  75%    912
  80%    918
  90%    942
  95%   1024
  98%   1283
  99%   1375
 100%   1449 (longest request)


Time: 00:09.205, Memory: 16.00 MB

OK (1 test, 3 assertions)
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
.                                                                   1 / 1 (100%)
This is ApacheBench, Version 2.3 <$Revision: 1843412 $>
Copyright 1996 Adam Twiss, Zeus Technology Ltd, http://www.zeustech.net/
Licensed to The Apache Software Foundation, http://www.apache.org/

Benchmarking 127.0.0.1 (be patient)


Server Software:        Hyperf
Server Hostname:        127.0.0.1
Server Port:            9501

Document Path:          /api/resetAccountTest/createOrderWithRt
Document Length:        24 bytes

Concurrency Level:      100
Time taken for tests:   18.640 seconds
Complete requests:      1000
Failed requests:        0
Total transferred:      167000 bytes
Total body sent:        166000
HTML transferred:       24000 bytes
Requests per second:    53.65 [#/sec] (mean)
Time per request:       1863.969 [ms] (mean)
Time per request:       18.640 [ms] (mean, across all concurrent requests)
Transfer rate:          8.75 [Kbytes/sec] received
                        8.70 kb/s sent
                        17.45 kb/s total

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        0    0   0.5      0       2
Processing:    72 1823 245.0   1816    3075
Waiting:       69 1823 245.0   1816    3075
Total:         72 1823 245.2   1816    3076

Percentage of the requests served within a certain time (ms)
  50%   1816
  66%   1837
  75%   1850
  80%   1860
  90%   1917
  95%   2082
  98%   2699
  99%   2897
 100%   3076 (longest request)


Time: 00:18.695, Memory: 16.00 MB

OK (1 test, 3 assertions)
```

## 参考教程
https://learnku.com/articles/62377


![](https://cdn.learnku.com/uploads/images/202202/14/46914/6N90FDXvSa.png!large)

扫码进微信群。希望有更多的朋友相互学习和一起研究分布式事务的知识。

