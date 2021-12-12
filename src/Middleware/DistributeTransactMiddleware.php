<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Windawake\HyperfResetTransaction\Middleware;

use Hyperf\DbConnection\Db;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Hyperf\Utils\Context;

class DistributeTransactMiddleware implements MiddlewareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $transactId = $request->getHeaderLine('transact_id');
        if ($transactId) {
            $sqlArr = Db::table('reset_transaction')->where('transact_id', $transactId)->pluck('sql')->toArray();
            $sql = implode(';', $sqlArr);
            Db::beginTransaction();
            if ($sqlArr) {
                Db::unprepared($sql);
            }

            Context::set('transact_id', $transactId);
        }

        $response = $handler->handle($request);

        $transactId = Context::get('transact_id');
        if ($transactId) {
            Db::rollBack();

            $sqlArr = Context::get('transact_sql');
            Context::destroy('transact_id');
            Context::destroy('transact_sql');

            if ($sqlArr) {
                foreach ($sqlArr as $item) {
                    Db::table('reset_transaction')->insert([
                        'transact_id' => $transactId,
                        'sql' => value($item['sql']),
                        'result' => $item['result'],
                    ]);
                }
            }
        }

        return $response;
    }
}
