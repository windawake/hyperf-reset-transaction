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

use App\Model\ResetProductModel;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\Middlewares;
use Windawake\HyperfResetTransaction\Middleware\DistributeTransactMiddleware;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\PutMapping;
use Hyperf\HttpServer\Annotation\DeleteMapping;

/**
 * @Controller(prefix="api/resetProduct")
 * @Middlewares({
 *     @Middleware(DistributeTransactMiddleware::class)
 * })
 */
class ResetProductController extends AbstractController
{

    /**
     * @GetMapping(path="")
     */
    public function index()
    {
        //
    }

    /**
     * @PostMapping(path="")
     */
    public function store()
    {
        //
        $data = $this->request->all();
        return ResetProductModel::create($data);
    }

    /**
     * @GetMapping(path="{id:\d+}")
     */
    public function show($id)
    {
        //
        $item = ResetProductModel::find($id);
        return $item ?? [];
    }

    /**
     * @PutMapping(path="{id:\d+}")
     */
    public function update($id)
    {
        //
        $item = ResetProductModel::findOrFail($id);
        $ret = $item->update($this->request->all());
        return ['result' => $ret];
    }

    /**
     * @DeleteMapping(path="{id:\d+}")
     */
    public function destroy($id)
    {
        //
        $item = ResetProductModel::findOrFail($id);
        $ret = $item->delete();
        return ['result' => $ret];
    }
}
