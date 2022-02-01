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

/**
 * @Controller(prefix="api/resetOrder")
 * @Middlewares({
 *      @Middleware(ServiceOrderMiddleware::class),
 *      @Middleware(DistributeTransactMiddleware::class)
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
}
