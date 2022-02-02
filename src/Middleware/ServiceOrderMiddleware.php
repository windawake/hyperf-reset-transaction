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

class ServiceOrderMiddleware implements MiddlewareInterface
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
        Context::set('rt_connection_defaultName', 'service_order');

        $response = $handler->handle($request);

        return $response;
    }
}
