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

namespace Windawake\HyperfResetTransaction;

use Hyperf\Database\Connection;
use Windawake\HyperfResetTransaction\Command\CreateExamplesCommand;
use Windawake\HyperfResetTransaction\Database\ResetMySqlConnection;

class ConfigProvider
{
    public function __invoke(): array
    {
        $config = [
            'commands' => [
                CreateExamplesCommand::class,
            ],
        ];
        // overide MySqlConnection
        Connection::resolverFor('mysql', function ($connection, $database, $prefix, $config) {

            return new ResetMySqlConnection($connection, $database, $prefix, $config);
        });

        return $config;
    }
}
