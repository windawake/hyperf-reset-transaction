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
return [
    'center' =>[
        'commit_url' => 'http://127.0.0.1:9503/api/resetTransaction/commit',
        'rollback_url' => 'http://127.0.0.1:9503/api/resetTransaction/rollback',
        'connections' => [
            'rt_center' => [
                'connection_name' => 'rt_center',
                'driver' => env('DB_DRIVER', 'mysql'),
                'host' => env('DB_HOST', 'localhost'),
                'database' => 'rt_center',
                'port' => env('DB_PORT', 3306),
                'username' => env('DB_USERNAME', 'root'),
                'password' => env('DB_PASSWORD', ''),
                'charset' => env('DB_CHARSET', 'utf8'),
                'collation' => env('DB_COLLATION', 'utf8_unicode_ci'),
                'prefix' => env('DB_PREFIX', ''),
                'pool' => [
                    'min_connections' => 1,
                    'max_connections' => 300,
                    'connect_timeout' => 10.0,
                    'wait_timeout' => 30,
                    'heartbeat' => -1,
                    'max_idle_time' => (float) env('DB_MAX_IDLE_TIME', 60),
                ],
                'commands' => [
                    'gen:model' => [
                        'path' => 'app/Model',
                        'force_casts' => true,
                        'inheritance' => 'Model',
                    ],
                ],
            ],
        ]
        
    ],
    'service_connections' => [
        'service_order' => [
            'connection_name' => 'service_order',
            'driver' => env('DB_DRIVER', 'mysql'),
            'host' => env('DB_HOST', 'localhost'),
            'database' => 'service_order',
            'port' => env('DB_PORT', 3306),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'collation' => env('DB_COLLATION', 'utf8_unicode_ci'),
            'prefix' => env('DB_PREFIX', ''),
            'pool' => [
                'min_connections' => 1,
                'max_connections' => 300,
                'connect_timeout' => 10.0,
                'wait_timeout' => 30,
                'heartbeat' => -1,
                'max_idle_time' => (float) env('DB_MAX_IDLE_TIME', 60),
            ],
            'commands' => [
                'gen:model' => [
                    'path' => 'app/Model',
                    'force_casts' => true,
                    'inheritance' => 'Model',
                ],
            ],
        ],
        'service_storage' => [
            'connection_name' => 'service_storage',
            'driver' => env('DB_DRIVER', 'mysql'),
            'host' => env('DB_HOST', 'localhost'),
            'database' => 'service_storage',
            'port' => env('DB_PORT', 3306),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'collation' => env('DB_COLLATION', 'utf8_unicode_ci'),
            'prefix' => env('DB_PREFIX', ''),
            'pool' => [
                'min_connections' => 1,
                'max_connections' => 300,
                'connect_timeout' => 10.0,
                'wait_timeout' => 30,
                'heartbeat' => -1,
                'max_idle_time' => (float) env('DB_MAX_IDLE_TIME', 60),
            ],
            'commands' => [
                'gen:model' => [
                    'path' => 'app/Model',
                    'force_casts' => true,
                    'inheritance' => 'Model',
                ],
            ],
        ],
        'service_account' => [
            'connection_name' => 'service_account',
            'driver' => env('DB_DRIVER', 'mysql'),
            'host' => env('DB_HOST', 'localhost'),
            'database' => 'service_account',
            'port' => env('DB_PORT', 3306),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'collation' => env('DB_COLLATION', 'utf8_unicode_ci'),
            'prefix' => env('DB_PREFIX', ''),
            'pool' => [
                'min_connections' => 1,
                'max_connections' => 300,
                'connect_timeout' => 10.0,
                'wait_timeout' => 30,
                'heartbeat' => -1,
                'max_idle_time' => (float) env('DB_MAX_IDLE_TIME', 60),
            ],
            'commands' => [
                'gen:model' => [
                    'path' => 'app/Model',
                    'force_casts' => true,
                    'inheritance' => 'Model',
                ],
            ],
        ],
    ]

];