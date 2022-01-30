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

use Hyperf\Database\Query\Processors\MySqlProcessor as Processor;
use Hyperf\Database\Query\Builder;
use Windawake\HyperfResetTransaction\Facades\RT;

class MySqlProcessor extends Processor
{
    /**
     * Process an  "insert get ID" query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $sql
     * @param  array  $values
     * @param  string|null  $sequence
     * @return int
     */
    public function processInsertGetId(Builder $query, $sql, $values, $sequence = null)
    {
        $id = parent::processInsertGetId($query, $sql, $values, $sequence);

        RT::saveQuery($sql, $values, 0, 0, $sequence, $id);

        return $id;
    }
}
