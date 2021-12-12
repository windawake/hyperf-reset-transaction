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


        $boolean = $this->filesystem->copyDirectory(__DIR__ . '/../../examples/Controller', BASE_PATH . '/app/Controller');

        $boolean = $this->filesystem->copyDirectory(__DIR__ . '/../../examples/Model', BASE_PATH . '/app/Model');

        if (!$boolean) {
            $this->error('Failed to create Example!');
        }

        $this->info('Example created successfully!');
    }
}
