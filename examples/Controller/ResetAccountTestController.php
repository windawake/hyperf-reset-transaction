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
use Windawake\HyperfResetTransaction\Middleware\ServiceAccountMiddleware;
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
use Hyperf\Database\ConnectionResolverInterface;

/**
 * @Controller(prefix="api/resetAccountTest")
 * @Middlewares({
 *      @Middleware(ServiceAccountMiddleware::class),
 *      @Middleware(DistributeTransactMiddleware::class)
 * })
 */
class ResetAccountTestController extends AbstractController
{
    /**
     * @PostMapping(path="createOrderWithLocal")
     */
    public function createOrderWithLocal()
    {
        $client = new Client([
            'timeout' => 300,
        ]);
        $orderNo = session_create_id();
        $stockQty = rand(1, 5);
        $amount = rand(1, 50)/10;
        Db::beginTransaction();
        
        $client->post('http://127.0.0.1:9502/api/resetOrder', [
            'json' => [
                'order_no' => $orderNo,
                'stock_qty' => $stockQty,
                'amount' => $amount
            ]
        ]);

        $response = $client->put('http://127.0.0.1:9502/api/resetStorage/1', [
            'json' => [
                'decr_stock_qty' => $stockQty
            ]
        ]);

        $resArr = json_decode($response->getBody()->getContents(), true);

        $rowCount = ResetAccountModel::where('id', 1)->where('amount', '>', $amount)->decrement('amount', $amount);

        $result = $resArr['result'] && $rowCount>0;
        Db::commit();

        return ['code' => 1, 'result' => $result];
    }

    /**
     * @PostMapping(path="createOrderWithRt")
     */
    public function createOrderWithRt()
    {
        $client = new Client([
            'timeout' => 300,
        ]);
        $orderNo = session_create_id();
        $stockQty = rand(1, 5);
        $amount = rand(1, 50)/10;
        $transactId = RT::beginTransaction();
        
        $client->post('http://127.0.0.1:9502/api/resetOrder', [
            'json' => [
                'order_no' => $orderNo,
                'stock_qty' => $stockQty,
                'amount' => $amount
            ],
            'headers' => [
                'rt_request_id' => session_create_id(),
                'rt_transact_id' => $transactId,
            ]
        ]);

        $response = $client->put('http://127.0.0.1:9502/api/resetStorage/1', [
            'json' => [
                'decr_stock_qty' => $stockQty
            ],
            'headers' => [
                'rt_request_id' => session_create_id(),
                'rt_transact_id' => $transactId,
            ]
        ]);

        $resArr = json_decode($response->getBody()->getContents(), true);

        $rowCount = ResetAccountModel::where('id', 1)->where('amount', '>', $amount)->decrement('amount', $amount);
        $result = $resArr['result'] && $rowCount>0;

        RT::commit();

        return ['code' => 1, 'result' => $result];
    }
}
