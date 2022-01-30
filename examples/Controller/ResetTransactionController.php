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

use Hyperf\DbConnection\Db;
use Hyperf\Utils\Arr;
use Windawake\HyperfResetTransaction\Exception\ResetTransactionException;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use Windawake\HyperfResetTransaction\Facades\RT;

/**
 * @Controller(prefix="api/resetTransaction")
 */
class ResetTransactionController extends AbstractController
{
    /**
     * @PostMapping(path="commit")
     */
    public function commit()
    {
        $transactId = $this->request->input('transact_id');
        $transactRollback = $this->request->input('transact_rollback', []);
        $code = 1;
        
        RT::centerCommit($transactId, $transactRollback);

        return ['code' => $code, 'transact_id' => $transactId];
    }

    /**
     * @PostMapping(path="rollback")
     */
    public function rollback()
    {
        //
        $transactId = $this->request->input('transact_id');
        $transactRollback = $this->request->input('transact_rollback', []);
        $code = 1;

        RT::centerRollback($transactId, $transactRollback);

        return ['code' => $code, 'transactId' => $transactId];
    }
}
