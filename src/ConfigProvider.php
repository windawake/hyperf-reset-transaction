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
        $configArr = [
            'dependencies' => [
                \Hyperf\DbConnection\Db::class => \Windawake\HyperfResetTransaction\Core\OverrideDb::class,
            ],
            'commands' => [
                CreateExamplesCommand::class,
            ],
            'databases' => $this->getRtDatabases()
        ];
        // overide MySqlConnection
        Connection::resolverFor('mysql', function ($connection, $database, $prefix, $configArr) {
            return new ResetMySqlConnection($connection, $database, $prefix, $configArr);
        });

        return $configArr;
    }

    private function getRtDatabases()
    {
        $rt = include BASE_PATH.'/config/autoload/rt_database.php';
        if ($rt) {
            return array_merge($rt['center']['connections'], $rt['service_connections']);
        }

        return [];
    }
}
