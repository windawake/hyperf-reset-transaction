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

/**
 * @Controller(prefix="api/resetTransaction")
 */
class ResetTransactionController extends AbstractController
{
    /**
     * @GetMapping(path="rollback")
     */
    public function rollback()
    {
        //
        $transactId = $this->request->header('transact_id');
        if($transactId) {
            Db::transaction(function () use ($transactId) {
                Db::table('reset_transaction')->where('transact_id', 'like', $transactId . '%')->delete();
            });
        }
        
        return 'success';
    }

    /**
     * @PostMapping(path="commit")
     */
    public function commit()
    {
        //
        $transactId = $this->request->header('transact_id');
        $transactRollback = $this->request->header('transact_rollback');
        // check the result of SQL execution
        $transactCheck = $this->request->header('transact_check');

        // delete rollback sql
        if ($transactRollback) {
            $transactRollback = json_decode($transactRollback, true);
            $transactRollback = Arr::dot($transactRollback);
            foreach ($transactRollback as $txId => $val) {
                $txId = str_replace('.', '-', $txId);
                Db::table('reset_transaction')->where('transact_id', 'like', $txId . '%')->delete();
            }
        }

        $sqlCollects = Db::table('reset_transaction')->where('transact_id', 'like', $transactId . '%')->get();
        if ($sqlCollects->count() > 0) {
            Db::transaction(function () use ($sqlCollects, $transactId, $transactCheck) {
                foreach ($sqlCollects as $item) {
                    $result = Db::getPdo()->exec($item->sql);
                    if ($transactCheck && $result != $item->result) {
                        throw new ResetTransactionException("db had been changed by anothor transact_id");
                    }
                }
                Db::table('reset_transaction')->where('transact_id', 'like', $transactId . '%')->delete();
            });
        }

        return 'success';
    }

}
