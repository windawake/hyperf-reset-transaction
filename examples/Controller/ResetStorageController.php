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

use App\Model\ResetStorageModel;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\Middlewares;
use Windawake\HyperfResetTransaction\Middleware\ServiceStorageMiddleware;
use Windawake\HyperfResetTransaction\Middleware\DistributeTransactMiddleware;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\PutMapping;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use GuzzleHttp\Client;
use Hyperf\DbConnection\Db;

/**
 * @Controller(prefix="api/resetStorage")
 * @Middlewares({
 *      @Middleware(ServiceStorageMiddleware::class),
 *      @Middleware(DistributeTransactMiddleware::class)
 * })
 */
class ResetStorageController extends AbstractController
{
    /**
     * @GetMapping(path="")
     */
    public function index()
    {
        //
        return ResetStorageModel::paginate();
    }

    /**
     * @PostMapping(path="")
     */
    public function store()
    {
        //
        return ResetStorageModel::create($this->request->all());
    }

    /**
     * @GetMapping(path="{id:\d+}")
     */
    public function show($id)
    {
        //
        $item = ResetStorageModel::find($id);
        return $item ?? [];
    }

    /**
     * @PutMapping(path="{id:\d+}")
     */
    public function update($id)
    {
        //
        $item = ResetStorageModel::findOrFail($id);
        if ($this->request->has('decr_stock_qty')) {
            $decrQty = (float) $this->request->input('decr_stock_qty');
            $ret = $item->where('stock_qty', '>', $decrQty)->decrement('stock_qty', $decrQty);
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
        $item = ResetStorageModel::findOrFail($id);
        $ret = $item->delete();
        return ['result' => $ret];
    }

    /**
     * @PutMapping(path="/updateWithCommit/{id:\d+}")
     */
    public function updateWithCommit($id)
    {
        $item = ResetStorageModel::findOrFail($id);
        Db::beginTransaction();

        if ($this->request->has('decr_stock_qty')) {
            $decrQty = (float) $this->request->input('decr_stock_qty');
            $ret = $item->where('stock_qty', '>', $decrQty)->decrement('stock_qty', $decrQty);
        } else {
            $ret = $item->update($this->request->all());
        }
        
        Db::commit();

        return ['result' => $ret];
    }
}
