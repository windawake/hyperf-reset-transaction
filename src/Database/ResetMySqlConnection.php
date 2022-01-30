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

use Hyperf\Database\MySqlConnection;
use Windawake\HyperfResetTransaction\Facades\RT;

class ResetMySqlConnection extends MySqlConnection
{
    /**
     * The switch of detecting sql
     *
     * @var bool
     */
    protected $checkResult = false;

    /**
     * Detect the return value when committing the transaction
     *
     * @param bool $checkResult
     */
    public function setCheckResult(bool $checkResult)
    {
        $this->checkResult = $checkResult;
        return $this;
    }

    /**
     * Detect the return value when committing the transaction
     *
     * @return bool
     */
    public function getCheckResult()
    {
        return $this->checkResult;
    }

    /**
     * Get the default query grammar instance.
     *
     * @return \Illuminate\Database\Query\Grammars\MySqlGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix(new MySqlGrammar);
    }

    /**
     * Get the default post processor instance.
     *
     * @return \Illuminate\Database\Query\Processors\MySqlProcessor
     */
    protected function getDefaultPostProcessor()
    {
        return new MySqlProcessor;
    }

    /**
     * Run an SQL statement and get the number of rows affected.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return int
     */
    public function affectingStatement($query, $bindings = []): int
    {
        $result = parent::affectingStatement($query, $bindings);

        RT::saveQuery($query, $bindings, $result, $this->checkResult);
        $this->checkResult = false;
        
        return (int) $result;
    }
}
