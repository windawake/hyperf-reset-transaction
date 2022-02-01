<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace HyperfTest\Transaction;

use PHPUnit\Framework\TestCase;
use App\Model\ResetAccountModel;
use Hyperf\DbConnection\Db;
use App\Model\ResetOrderModel;
use App\Model\ResetStorageModel;
use GuzzleHttp\Client;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Context;
use Hyperf\Contract\ConfigInterface;
use Hyperf\DbConnection\Pool\DbPool;
use Windawake\HyperfResetTransaction\Facades\RT;
use Hyperf\Database\ConnectionResolverInterface;

class ServiceTest extends TestCase
{
    private $baseUri = 'http://127.0.0.1:9501';

    /**
     * Client
     *
     * @var \GuzzleHttp\Client
     */
    private $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new Client([
            'base_uri' => $this->baseUri,
            'timeout' => 300,
        ]);

        $requestId = session_create_id();
        Context::set('rt_request_id', $requestId);

        // $config = ApplicationContext::getContainer()->get(ConfigInterface::class);
        // $databases = $config->get('databases');
        // $databases['default'] = $databases['service_order'];
        // $config->set('databases', $databases);

        $resolver = ApplicationContext::getContainer()->get(ConnectionResolverInterface::class);
        $resolver->setDefaultConnection('service_order');
    }

    // public function testRtDemo01()
    // {
    //     $orderNo = rand(1000, 9999); // 随机订单号
    //     $stockQty = 2; // 占用2个库存数量
    //     $amount = 20.55; // 订单总金额20.55元
    //     RT::beginTransaction();

    //     // Db::table('reset_order')->insertGetId([
    //     //     'order_no' => $orderNo,
    //     //     'stock_qty' => $stockQty,
    //     //     'amount' => $amount
    //     // ], 'id');

    //     ResetOrderModel::create([
    //         'order_no' => $orderNo,
    //         'stock_qty' => $stockQty,
    //         'amount' => $amount
    //     ]);

    //     RT::commitTest();
    //     // RT::commit();
    //     $this->assertTrue(true);
    // }

    public function testCreateOrderWithRollback()
    {
        $orderCount1 = ResetOrderModel::count();
        $storageItem1 = Db::connection('service_storage')->table('reset_storage')->find(1);
        $accountItem1 = Db::connection('service_account')->table('reset_account')->find(1);

        $transactId = RT::beginTransaction();
        $orderNo = rand(1000, 9999); // 随机订单号
        $stockQty = 2; // 占用2个库存数量
        $amount = 20.55; // 订单总金额20.55元

        ResetOrderModel::create([
            'order_no' => $orderNo,
            'stock_qty' => $stockQty,
            'amount' => $amount
        ]);

        $requestId = session_create_id();
        // 请求库存服务，减库存
        $response = $this->client->put('/api/resetStorage/1', [
            'json' => [
                'decr_stock_qty' => $stockQty
            ],
            'headers' => [
                'rt_request_id' => $requestId,
                'rt_transact_id' => $transactId,
            ]
        ]);
        $resArr1 = $this->responseToArray($response);
        $this->assertTrue($resArr1['result'] == 1, 'lack of stock'); //返回值是1，说明操作成功

        $requestId = session_create_id();
        // 请求账户服务，减金额
        $response = $this->client->put('/api/resetAccount/1', [
            'json' => [
                'decr_amount' => $amount
            ],
            'headers' => [
                'rt_request_id' => $requestId,
                'rt_transact_id' => $transactId,
            ]
        ]);
        $resArr2 = $this->responseToArray($response);
        $this->assertTrue($resArr2['result'] == 1, 'not enough money'); //返回值是1，说明操作成功

        RT::rollBack();

        $orderCount2 = ResetOrderModel::count();
        $storageItem2 = Db::connection('service_storage')->table('reset_storage')->find(1);
        $accountItem2 = Db::connection('service_account')->table('reset_account')->find(1);

        $this->assertTrue($orderCount1 == $orderCount2);
        $this->assertTrue($storageItem1->stock_qty == $storageItem2->stock_qty);
        $this->assertTrue($accountItem1->amount == $accountItem2->amount);
    }

    public function testCreateOrderWithCommit()
    {
        $orderCount1 = ResetOrderModel::count();
        $storageItem1 = ResetStorageModel::find(1);
        $accountItem1 = ResetAccountModel::find(1);

        $transactId = RT::beginTransaction();
        $orderNo = rand(1000, 9999); // 随机订单号
        $stockQty = 2; // 占用2个库存数量
        $amount = 20.55; // 订单总金额20.55元

        ResetOrderModel::create([
            'order_no' => $orderNo,
            'stock_qty' => $stockQty,
            'amount' => $amount
        ]);
        $requestId = session_create_id();

        // 请求库存服务，减库存
        $response = $this->client->put('/api/resetStorage/1', [
            'json' => [
                'decr_stock_qty' => $stockQty
            ],
            'headers' => [
                'rt_request_id' => $requestId,
                'rt_transact_id' => $transactId,
            ]
        ]);
        $resArr1 = $this->responseToArray($response);
        $this->assertTrue($resArr1['result'] == 1, 'lack of stock'); //返回值是1，说明操作成功

        $requestId = session_create_id();
        // 请求账户服务，减金额
        $response = $this->client->put('/api/resetAccount/1', [
            'json' => [
                'decr_amount' => $amount
            ],
            'headers' => [
                'rt_request_id' => $requestId,
                'rt_transact_id' => $transactId,
            ]
        ]);
        $resArr2 = $this->responseToArray($response);
        $this->assertTrue($resArr2['result'] == 1, 'not enough money'); //返回值是1，说明操作成功

        RT::commit();

        $orderCount2 = ResetOrderModel::count();
        $storageItem2 = ResetStorageModel::find(1);
        $accountItem2 = ResetAccountModel::find(1);

        $this->assertTrue(($orderCount1 + 1) == $orderCount2);
        $this->assertTrue($storageItem1->stock_qty - $stockQty == $storageItem2->stock_qty);
        $this->assertTrue(abs($accountItem1->amount - $amount - $accountItem2->amount) < 0.001);
    }

    public function testNestedTransaction()
    {
        for ($id = 11; $id <= 13; $id++) {
            $orderNo = rand(1000, 9999); // 随机订单号

            ResetOrderModel::updateOrCreate([
                'id' => $id,
            ], [
                'order_no' => $orderNo,
            ]);
        }

        $status = 11;

        $txId = RT::beginTransaction();
        $this->client->put('api/resetOrder/11', [
            'json' => [
                'order_no' => 'aaa',
                'status' => $status,
            ],
            'headers' => [
                'rt_request_id' => session_create_id(),
                'rt_transact_id' => $txId,
            ]
        ]);
            $txId2 = RT::beginTransaction();
            $this->client->put('api/resetOrder/12', [
                'json' => [
                    'order_no' => 'bbb',
                    'status' => $status,
                ],
                'headers' => [
                    'rt_request_id' => session_create_id(),
                    'rt_transact_id' => $txId2,
                ]
            ]);

                $txId3 = RT::beginTransaction();
                $this->client->put('api/resetOrder/13', [
                    'json' => [
                        'order_no' => 'ccc',
                        'status' => $status,
                    ],
                    'headers' => [
                        'rt_request_id' => session_create_id(),
                        'rt_transact_id' => $txId3,
                    ]
                ]);

                $response = $this->client->get('api/resetOrder', [
                    'json' => [
                        'status' => $status,
                    ],
                    'headers' => [
                        'rt_request_id' => session_create_id(),
                        'rt_transact_id' => $txId3,
                    ]
                ]);
                $resArr = $this->responseToArray($response);
                // 3层事务内，有3个订单修改了状态
                $this->assertTrue($resArr['total'] == 3);

                RT::commit();

            RT::rollBack();

        RT::commit();

        $response = $this->client->get('api/resetOrder', [
            'json' => [
                'status' => $status,
            ],
        ]);
        $resArr = $this->responseToArray($response);
        // 3层事务内，有3个订单修改了状态，但是第2层事务回滚了， 只有第1层事务成功提交
        $this->assertTrue($resArr['total'] == 1);
    }

    private function responseToArray($response)
    {
        $contents = $response->getBody()->getContents();
        return json_decode($contents, true);
    }
}
