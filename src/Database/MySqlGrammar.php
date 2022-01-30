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


use Hyperf\Database\Query\Grammars\MySqlGrammar as Grammar;
use Windawake\HyperfResetTransaction\Facades\RT;
use Hyperf\Utils\Context;

class MySqlGrammar extends Grammar
{
    /**
     * Compile the SQL statement to define a savepoint.
     *
     * @param  string  $name
     * @return string
     */
    public function compileSavepoint($name)
    {
        $sql = 'SAVEPOINT '.$name;
        
        $transactId = RT::getTransactId();
        $stmt = Context::get('rt_stmt');
        if ($transactId && is_null($stmt)) {
            RT::saveQuery($sql, [], 0, 0);
        }

        return $sql;
    }

    /**
     * Compile the SQL statement to execute a savepoint rollback.
     *
     * @param  string  $name
     * @return string
     */
    public function compileSavepointRollBack($name)
    {
        $sql = 'ROLLBACK TO SAVEPOINT '.$name;

        $transactId = RT::getTransactId();
        $stmt = Context::get('rt_stmt');
        if ($transactId && is_null($stmt)) {
            RT::saveQuery($sql, [], 0, 0);
        }

        return $sql;
    }
}
