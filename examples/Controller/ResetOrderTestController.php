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

namespace App\Controller;

use App\Model\ResetOrderModel;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\Middlewares;
use Windawake\HyperfResetTransaction\Middleware\ServiceOrderMiddleware;
use Windawake\HyperfResetTransaction\Middleware\DistributeTransactMiddleware;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\PutMapping;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use GuzzleHttp\Client;
use Hyperf\DbConnection\Db;
use Windawake\HyperfResetTransaction\Facades\RT;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;

/**
 * @Controller(prefix="api/resetOrderTest")
 * @Middlewares({
 *      @Middleware(ServiceOrderMiddleware::class),
 *      @Middleware(DistributeTransactMiddleware::class)
 * })
 */
class ResetOrderTestController extends AbstractController
{
    /**
     * @PostMapping(path="deadlockWithLocal")
     */
    public function deadlockWithLocal()
    {
        Db::beginTransaction();

        $s = ($this->request->getPort()) % 2;
        for ($i = $s; $i < 3; $i++) {
            $id = $i % 2 + 1;
            $attrs = ['id' => $id];
            $values = ['order_no' => session_create_id()];

            ResetOrderModel::updateOrCreate($attrs, $values);
            usleep(rand(1, 200) * 1000);
        }
        Db::commit();
        return ['code' => 1];
    }

    /**
     * @PostMapping(path="deadlockWithRt")
     */
    public function deadlockWithRt()
    {
        $transactId = RT::beginTransaction();
        $s = ($this->request->getPort()) % 2;
        for ($i = $s; $i < 3; $i++) {
            $id = $i % 2 + 1;
            // $attrs = ['id' => $id];
            // $values = ['order_no' => session_create_id()];
            // ResetOrderModel::updateOrCreate($attrs, $values);
            
            $client = new Client([
                'base_uri' => 'http://127.0.0.1:8002',
                'timeout' => 60,
            ]);
            $client->put('/api/resetOrderTest/updateOrCreate/'.$id, [
                'json' =>['order_no' => session_create_id()],
                'headers' => [
                    'rt_request_id' => session_create_id(),
                    'rt_transact_id' => $transactId,
                    'rt_connection' => 'service_order'
                ]
            ]);
            
            usleep(rand(1, 200) * 1000);
        }
        RT::commit();
        return ['code' => 1];
    }

        /**
     * @PostMapping(path="orderCommit")
     */
    public function orderCommit()
    {
        // Db::beginTransaction();
        RT::beginTransaction();
        $orderNo = session_create_id();
        $stockQty = rand(1, 5);
        $amount = rand(1, 50) / 10;
        ResetOrderModel::create(['order_no' => $orderNo, 'stock_qty' => $stockQty, 'amount' => $amount]);
        sleep(6);
        // Db::commit();
        RT::commit();
        return ['code' => 1];
    }

    /**
     * @PostMapping(path="orderRollback")
     */
    public function orderRollback()
    {
        // Db::beginTransaction();
        // RT::beginTransaction();
        // $orderNo = session_create_id();
        // $stockQty = rand(1, 5);
        // $amount = rand(1, 50) / 10;
        // ResetOrderModel::create(['order_no' => $orderNo, 'stock_qty' => $stockQty, 'amount' => $amount]);
        Db::rollback();
        // RT::rollback();
        return ['code' => 1];
    }

    /**
     * @PostMapping(path="orderWithLocal")
     */
    public function orderWithLocal()
    {
        Db::beginTransaction();
        $orderNo = session_create_id();
        $stockQty = rand(1, 5);
        $amount = rand(1, 50)/10;

        ResetOrderModel::create([
            'order_no' => $orderNo,
            'stock_qty' => $stockQty,
            'amount' => $amount
        ]);

        
        Db::commit();

        for ($i = 0; $i < 10; $i++) {
            ResetOrderModel::first();
        }

        return ['code' => 1];
    }

    /**
     * @PostMapping(path="orderWithRt")
     */
    public function orderWithRt()
    {
        // $logger = ApplicationContext::getContainer()->get(LoggerFactory::class)->get('sql');

        RT::beginTransaction();
        $orderNo = session_create_id();
        $stockQty = rand(1, 5);
        $amount = rand(1, 50)/10;

        ResetOrderModel::create([
            'order_no' => $orderNo,
            'stock_qty' => $stockQty,
            'amount' => $amount
        ]);

        // $logger->info('orderWithRt');
        RT::commit();
        return ['code' => 1];
    }

    /**
     * @PostMapping(path="disorderWithLocal")
     */
    public function disorderWithLocal()
    {
        Db::beginTransaction();
        usleep(rand(1, 200) * 1000);
        $orderNo = session_create_id();
        $stockQty = rand(1, 5);
        $amount = rand(1, 50)/10;
        $status = rand(1, 3);

        $item = ResetOrderModel::updateOrCreate([
            'id' => rand(1, 10),
        ], [
            'order_no' => $orderNo,
            'stock_qty' => $stockQty,
            'amount' => $amount,
            'status' => $status,
        ]);


        $item = ResetOrderModel::find(rand(1, 10));
        if ($item) {
            $item->delete();
        }

        if (rand(0,1) == 0) {
            ResetOrderModel::where('status', $status)->update(['stock_qty' => rand(1, 5)]);
        }

        Db::commit();
        return ['code' => 1];
    }

    /**
     * @PostMapping(path="disorderWithRt")
     */
    public function disorderWithRt()
    {
        RT::beginTransaction();
        usleep(rand(1, 200) * 1000);
        $orderNo = session_create_id();
        $stockQty = rand(1, 5);
        $amount = rand(1, 50)/10;
        $status = rand(1, 3);

        $item = ResetOrderModel::updateOrCreate([
            'id' => rand(1, 10),
        ], [
            'order_no' => $orderNo,
            'stock_qty' => $stockQty,
            'amount' => $amount,
            'status' => $status,
        ]);


        $item = ResetOrderModel::find(rand(1, 10));
        if ($item) {
            $item->delete();
        }

        if (rand(0,1) == 0) {
            ResetOrderModel::where('status', $status)->update(['stock_qty' => rand(1, 5)]);
        }

        RT::commit();
        return ['code' => 1];
    }
}
