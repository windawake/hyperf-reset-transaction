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
use Hyperf\Utils\ApplicationContext;
use Hyperf\Contract\ConfigInterface;
use Hyperf\HttpServer\Response;
use Windawake\HyperfResetTransaction\Exception\ResetTransactionException;
use Windawake\HyperfResetTransaction\Facades\RT;
use Hyperf\Database\ConnectionResolverInterface;

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
        $requestId = $request->getHeaderLine('rt_request_id');
        $transactId = $request->getHeaderLine('rt_transact_id');
        
        if ($transactId) {
            if (!$requestId) {
                throw new ResetTransactionException('rt_request_id cannot be null');
            }
            Context::set('rt_request_id', $requestId);
            $item = DB::connection('rt_center')->table('reset_transact_req')->where('request_id', $requestId)->first();
            if ($item) {
                $data = json_decode($item->response, true);
                return Response::json($data);
            }

            RT::middlewareBeginTransaction($transactId);
        }

        $response = $handler->handle($request);

        $requestId = $request->getHeaderLine('rt_request_id');
        $transactId = $request->getHeaderLine('rt_transact_id');
        $transactIdArr = explode('-', $transactId);
        if ($transactId && $response->isSuccessful()) {
            RT::middlewareRollback();
            DB::connection('rt_center')->table('reset_transact_req')->insert([
                'transact_id' => $transactIdArr[0],
                'request_id' => $requestId,
                'response' => $response->getBody()->getContents(),
            ]);
        }

        return $response;
    }
}
