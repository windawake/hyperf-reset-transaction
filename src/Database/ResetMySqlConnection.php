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

namespace Windawake\HyperfResetTransaction\Database;

use Hyperf\Database\Exception\QueryException;
use Hyperf\Database\MySqlConnection;
use Closure;
use Hyperf\DbConnection\Db;
use Hyperf\Utils\Context;

class ResetMySqlConnection extends MySqlConnection
{
    /**
     * Run a SQL statement and log its execution context.
     *
     * @throws QueryException
     */
    protected function run(string $query, array $bindings, Closure $callback)
    {
        $result = parent::run($query, $bindings, $callback);

        $transactId = Context::get('transact_id');
        if ($transactId && $query && !strpos($query, 'reset_transaction')) {
            $action = strtolower(substr(trim($query), 0, 6));
            $sql = str_replace("?", "'%s'", $query);
            $completeSql = vsprintf($sql, $bindings);

            if (in_array($action, ['insert', 'update', 'delete'])) {
                $backupSql = $completeSql;
                if ($action == 'insert') {
                    $lastId = $this->getPdo()->lastInsertId();
                    // extract variables from sql
                    preg_match("/insert into (.+) \((.+)\) values \((.+)\)/", $backupSql, $match);
                    $database = $this->getConfig('database');
                    $table = $match[1];
                    $columns = $match[2];
                    $parameters = $match[3];

                    $backupSql = function () use ($database, $table, $columns, $parameters, $lastId) {
                        $columnItem = Db::selectOne('select column_name as `column_name` from information_schema.columns where table_schema = ? and table_name = ? and column_key="PRI"', [$database, trim($table, '`')]);
                        $primaryKey = $columnItem->column_name;

                        $columns = "`{$primaryKey}`, " . $columns;

                        $parameters = "'{$lastId}', " . $parameters;
                        return "insert into $table ($columns) values ($parameters)";
                    };
                }

                $sqlItem = ['sql' => $backupSql, 'result' => $result];
                $arr = Context::get('transact_sql', []);
                $arr[] = $sqlItem;
                Context::set('transact_sql', $arr);
            }

            // Log::info($completeSql);
        }

        return $result;
    }
}
