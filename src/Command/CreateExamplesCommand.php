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

namespace Windawake\HyperfResetTransaction\Command;

use Hyperf\Command\Command;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;
use Psr\Container\ContainerInterface;
use Hyperf\Utils\Filesystem\Filesystem;
use Hyperf\DbConnection\Db;

class CreateExamplesCommand extends Command
{
    protected $container;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct('resetTransact:create-examples');

        $this->container = $container;
        $this->filesystem = $container->get(Filesystem::class);
    }

    public function handle()
    {
        //
        $this->addFileToApp();
        $this->addTableToDatabase();
        $this->addTestsuitToPhpunit();

        $this->info('Example created successfully!');

        $transactTable = 'reset_transaction';
        $productTable = 'reset_product';
        Schema::dropIfExists($transactTable);
        Schema::create($transactTable, function (Blueprint $table) {
            $table->increments('id');
            $table->string('transact_id', 512);
            $table->text('sql');
            $table->integer('result')->default(0);
            $table->dateTime('created_at')->useCurrent();
            $table->index('transact_id');
        });

        Schema::dropIfExists($productTable);
        Schema::create($productTable, function (Blueprint $table) {
            $table->increments('pid');
            $table->integer('store_id')->default(0);
            $table->string('product_name')->default('');
            $table->tinyInteger('status')->default(0);
            $table->dateTime('created_at')->useCurrent();
        });
    }

    /**
     * rewrite phpunit.xml
     *
     * @return void
     */
    private function addTestsuitToPhpunit()
    {
        $content = file_get_contents(BASE_PATH.'/phpunit.xml');
        $xml = new \SimpleXMLElement($content);
        $hasTransaction = false;

        foreach ($xml->testsuites->testsuite as $testsuite) {
            if ($testsuite->attributes()->name == 'Transaction') {
                $hasTransaction = true;
            }
        }

        if ($hasTransaction == false) {
            $testsuite = $xml->testsuites->addChild('testsuite');
            $testsuite->addAttribute('name', 'Transaction');
            $directory = $testsuite->addChild('directory', './test/Transaction');
            $directory->addAttribute('suffix', 'Test.php');

            $domxml = new \DOMDocument('1.0');
            $domxml->preserveWhiteSpace = false;
            $domxml->formatOutput = true;
            $domxml->loadXML($xml->asXML());
            $domxml->save(BASE_PATH.'/phpunit.xml');
        }
    }

    /**
     * db
     *
     * @return void
     */
    private function addTableToDatabase()
    {
        $transactTable = 'reset_transact';
        $transactSqlTable = 'reset_transact_sql';
        $transactReqTable = 'reset_transact_req';
        $orderTable = 'reset_order';
        $storageTable = 'reset_storage';
        $accountTable = 'reset_account';

        $orderService = 'service_order';
        $storageService = 'service_storage';
        $accountService = 'service_account';
        $rtCenter = 'rt_center';

        $serviceMap = [
            $orderService => [
                $orderTable
            ],
            $storageService => [
                $storageTable,
            ],
            $accountService => [
                $accountTable
            ],
            $rtCenter => [
                $transactTable, $transactSqlTable, $transactReqTable,
            ]
        ];

        $manager = Db::getDoctrineSchemaManager();
        $dbList = $manager->listDatabases();

        foreach ($serviceMap as $service => $tableList) {
            if (!in_array($service, $dbList)) {
                $manager->createDatabase($service);
            }

            foreach ($tableList as $table) {
                if ($table == $transactTable) {
                    $fullTable = $service . '.' . $transactTable;
                    Schema::dropIfExists($fullTable);
                    Schema::create($fullTable, function (Blueprint $table) {
                        $table->bigIncrements('id');
                        $table->string('transact_id', 32);
                        $table->tinyInteger('action')->default(0);
                        $table->text('transact_rollback');
                        $table->dateTime('created_at')->useCurrent();
                        $table->unique('transact_id');
                    });
                }

                if ($table == $transactSqlTable) {
                    $fullTable = $service . '.' . $transactSqlTable;
                    Schema::dropIfExists($fullTable);
                    Schema::create($fullTable, function (Blueprint $table) {
                        $table->bigIncrements('id');
                        $table->string('request_id', 32);
                        $table->string('transact_id', 512);
                        $table->tinyInteger('transact_status')->default(0);
                        $table->string('connection', 32);
                        $table->text('sql');
                        $table->integer('result')->default(0);
                        $table->tinyInteger('check_result')->default(0);
                        $table->dateTime('created_at')->useCurrent();
                        $table->index('request_id');
                        $table->index('transact_id');
                    });
                }

                if ($table == $transactReqTable) {
                    $fullTable = $service . '.' . $transactReqTable;
                    Schema::dropIfExists($fullTable);
                    Schema::create($fullTable, function (Blueprint $table) {
                        $table->bigIncrements('id');
                        $table->string('request_id', 32);
                        $table->string('transact_id', 32);
                        $table->text('response');
                        $table->dateTime('created_at')->useCurrent();
                        $table->unique('request_id');
                        $table->index('transact_id');
                    });
                }

                if ($table == $orderTable) {
                    $fullTable = $service . '.' . $orderTable;
                    Schema::dropIfExists($fullTable);
                    Schema::create($fullTable, function (Blueprint $table) {
                        $table->increments('id');
                        $table->string('order_no')->default('');
                        $table->integer('stock_qty')->default(0);
                        $table->decimal('amount')->default(0);
                        $table->tinyInteger('status')->default(0);
                        $table->unique('order_no');
                    });
                }

                if ($table == $storageTable) {
                    $fullTable = $service . '.' . $storageTable;
                    Schema::dropIfExists($fullTable);
                    Schema::create($fullTable, function (Blueprint $table) {
                        $table->increments('id');
                        $table->integer('stock_qty')->default(0);
                    });
                    Db::unprepared("insert into {$fullTable} values(1, 1000)");
                }

                if ($table == $accountTable) {
                    $fullTable = $service . '.' . $accountTable;
                    Schema::dropIfExists($fullTable);
                    Schema::create($fullTable, function (Blueprint $table) {
                        $table->increments('id');
                        $table->decimal('amount')->default(0);
                    });
                    Db::unprepared("insert into {$fullTable} values(1, 10000)");
                }
            }
        }
    }

    private function addFileToApp()
    {
        $this->filesystem->copyDirectory(__DIR__ . '/../../examples/Controller', BASE_PATH.'/app/Controller');
        $this->filesystem->copyDirectory(__DIR__ . '/../../examples/Model', BASE_PATH.'/app/Model');
        $this->filesystem->copyDirectory(__DIR__ . '/../../examples/config', BASE_PATH.'/config/autoload');
        $this->filesystem->copyDirectory(__DIR__ . '/../../examples/test', BASE_PATH.'/test');
    }
}
