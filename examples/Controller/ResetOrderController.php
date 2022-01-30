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
use Windawake\HyperfResetTransaction\Middleware\DistributeTransactMiddleware;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\PutMapping;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use GuzzleHttp\Client;
use Hyperf\DbConnection\Db;
use Windawake\HyperfResetTransaction\Facades\RT;

/**
 * @Controller(prefix="api/resetOrder")
 * @Middlewares({
 *     @Middleware(DistributeTransactMiddleware::class)
 * })
 */
class ResetOrderController extends AbstractController
{
    /**
     * @GetMapping(path="")
     */
    public function index()
    {
        //
        $query = ResetOrderModel::query();
        if ($this->request->has('status')) {
            $query->where('status', $this->request->input('status'));
        }
        return $query->paginate();
    }

    /**
     * @PostMapping(path="")
     */
    public function store()
    {
        //
        return ResetOrderModel::create($this->request->all());
    }

    /**
     * @GetMapping(path="{id:\d+}")
     */
    public function show($id)
    {
        //
        $item = ResetOrderModel::find($id);
        return $item ?? [];
    }

    /**
     * @PutMapping(path="{id:\d+}")
     */
    public function update($id)
    {
        //
        $item = ResetOrderModel::findOrFail($id);
        $ret = $item->update($this->request->all());
        return ['result' => $ret];
    }

    /**
     * @DeleteMapping(path="{id:\d+}")
     */
    public function destroy($id)
    {
        //
        $item = ResetOrderModel::findOrFail($id);
        $ret = $item->delete();
        return ['result' => $ret];
    }

    /**
     * @PutMapping(path="updateOrCreate/{id:\d+}")
     */
    public function updateOrCreate($id)
    {
        //
        $attr = ['id' => $id];
        $item = ResetOrderModel::updateOrCreate($attr, $this->request->all());
        return $item;
    }

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
    }

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
    }

    public function orderWithLocal()
    {
        Db::beginTransaction();
        usleep(rand(1, 200) * 1000);
        $orderNo = session_create_id();
        $stockQty = rand(1, 5);
        $amount = rand(1, 50)/10;

        $item = ResetOrderModel::create([
            'order_no' => $orderNo,
            'stock_qty' => $stockQty,
            'amount' => $amount
        ]);

        $item->increment('stock_qty');
        Db::commit();
    }

    public function orderWithRt()
    {
        RT::beginTransaction();
        usleep(rand(1, 200) * 1000);
        $orderNo = session_create_id();
        $stockQty = rand(1, 5);
        $amount = rand(1, 50)/10;

        $item = ResetOrderModel::create([
            'order_no' => $orderNo,
            'stock_qty' => $stockQty,
            'amount' => $amount
        ]);

        $item->increment('stock_qty');
        RT::commit();
    }

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
    }

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
    }
}
