<?php

namespace Windawake\HyperfResetTransaction\Facades;

use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Context;

/**
 * @method static string beginTransaction()
 * @method static mixed commit()
 * @method static mixed rollBack()
 * @method static void setTransactId(string $transactId)
 * @method static string getTransactId()
 * @method static void middlewareRollback()
 * @method static void middlewareBeginTransaction(string $transactId)
 * @method static mixed centerCommit(string $transactId, array $transactRollback)
 * @method static mixed centerRollback(string $transactId, array $transactRollback)
 * @method static void saveQuery(string $query, array $bindings, int $result, bool $checkResult, string $keyName = null, int $id = null)
 * 
 * @see \Windawake\HyperfResetTransaction\Facades\ResetTransaction
 *
 */
class RT
{
    const STATUS_START = 0;
    const STATUS_COMMIT = 1;
    const STATUS_ROLLBACK = 2;
    
    public static function __callStatic($name, $arguments)
    {
        $id = 'rt_facade';
        if (Context::has($id)) {
            $rt = Context::get($id);
        } else {
            $rt = new ResetTransaction();
            Context::set($id, $rt);
        }

        return $rt->{$name}(...$arguments);
    }
}
