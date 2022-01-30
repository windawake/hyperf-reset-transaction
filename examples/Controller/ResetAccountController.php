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

use App\Model\ResetAccountModel;
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
 * @Controller(prefix="api/resetAccount")
 * @Middlewares({
 *     @Middleware(DistributeTransactMiddleware::class)
 * })
 */
class ResetAccountController extends AbstractController
{

    /**
     * @GetMapping(path="")
     */
    public function index()
    {
        //
        return ResetAccountModel::paginate();
    }

    /**
     * @PostMapping(path="")
     */
    public function store()
    {
        //
        return ResetAccountModel::create($this->request->all());
    }

    /**
     * @GetMapping(path="{id:\d+}")
     */
    public function show($id)
    {
        //
        $item = ResetAccountModel::find($id);
        return $item ?? [];
    }

    /**
     * @PutMapping(path="{id:\d+}")
     *

     * @param  int  $id
     * 

     */
    public function update($id)
    {
        //
        $item = ResetAccountModel::findOrFail($id);
        if ($this->request->has('decr_amount')) {
            $decrAmount = (float) $this->request->input('decr_amount');
            $ret = $item->where('amount', '>', $decrAmount)->decrement('amount', $decrAmount);
        } else {
            $ret = $item->update($this->request->all());
        }

        return ['result' => $ret];
    }

    /**
     * @DeleteMapping(path="{id:\d+}")
     */
    public function destroy($id)
    {
        //
        $item = ResetAccountModel::findOrFail($id);
        $ret = $item->delete();
        return ['result' => $ret];
    }

    /**
     * transaction create order then commit
     *

     */
    public function createOrdersCommit()
    {
        $client = new Client([
            'timeout' => 30,
        ]);
        $orderNo = session_create_id();
        $stockQty = rand(1, 5);
        $amount = rand(1, 50)/10;
        $transactId = RT::beginTransaction();
        
        $client->post('http://127.0.0.1:8003/api/resetOrder', [
            'json' => [
                'order_no' => $orderNo,
                'stock_qty' => $stockQty,
                'amount' => $amount
            ],
            'headers' => [
                'rt_request_id' => session_create_id(),
                'rt_transact_id' => $transactId,
                'rt_connection' => 'service_order'
            ]
        ]);

        $response = $client->put('http://127.0.0.1:8004/api/resetStorage/1', [
            'json' => [
                'decr_stock_qty' => $stockQty
            ],
            'headers' => [
                'rt_request_id' => session_create_id(),
                'rt_transact_id' => $transactId,
                'rt_connection' => 'service_storage'
            ]
        ]);

        $resArr = json_decode($response->getBody()->getContents(), true);

        $rowCount = ResetAccountModel::setCheckResult(true)->where('id', 1)->where('amount', '>', $amount)->decrement('amount', $amount);

        $result = $resArr['result'] && $rowCount>0;

        RT::commit();

        return ['result' => $result];
    }

    /**
     * transaction create order then rollBack
     *

     */
    public function createOrdersRollback()
    {
        $client = new Client([
            'timeout' => 30,
        ]);
        $orderNo = session_create_id();
        $stockQty = rand(1, 5);
        $amount = rand(1, 50)/10;
        $transactId = RT::beginTransaction();
        
        $client->post('http://127.0.0.1:8003/api/resetOrder', [
            'json' => [
                'order_no' => $orderNo,
                'stock_qty' => $stockQty,
                'amount' => $amount
            ],
            'headers' => [
                'rt_request_id' => session_create_id(),
                'rt_transact_id' => $transactId,
                'rt_connection' => 'service_order'
            ]
        ]);

        $client->put('http://127.0.0.1:8004/api/resetStorageTest/updateWithCommit/1', [
            'json' => [
                'decr_stock_qty' => $stockQty
            ],
            'headers' => [
                'rt_request_id' => session_create_id(),
                'rt_transact_id' => $transactId,
                'rt_connection' => 'service_storage'
            ]
        ]);

        ResetAccountModel::setCheckResult(true)->where('id', 1)->where('amount', '>', $amount)->decrement('amount', $amount);

        RT::rollBack();

        return ['result' => true];
    }
}
