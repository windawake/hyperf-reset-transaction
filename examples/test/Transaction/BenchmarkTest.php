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
namespace HyperfTest\Transaction;

use PHPUnit\Framework\TestCase;
use Hyperf\DbConnection\Db;
use App\Model\ResetOrderModel;
use App\Model\ResetAccountModel;
use App\Model\ResetStorageModel;

class BenchmarkTest extends TestCase
{
    protected $urlOne = 'http://127.0.0.1:9501/api';
    protected $urlTwo = 'http://127.0.0.1:9502/api';

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testDeadlock01()
    {
        // $con = Db::connection('service_order');

        $shellOne = "ab -n 12 -c 4 {$this->urlOne}/resetOrderTest/deadlockWithLocal";
        $shellTwo = "ab -n 12 -c 4 {$this->urlTwo}/resetOrderTest/deadlockWithLocal";

        $shell = sprintf("%s & %s", $shellOne, $shellTwo);
        exec($shell, $output, $resultCode);

        // $sql = "SHOW ENGINE INNODB STATUS";
        // $ret = $con->select($sql);
        // Log::info($ret);
        
    }

    public function testDeadlock02()
    {
        $shellOne = "ab -n 12 -c 4 {$this->urlOne}/resetOrderTest/deadlockWithRt";
        $shellTwo = "ab -n 12 -c 4 {$this->urlTwo}/resetOrderTest/deadlockWithRt";

        $shell = sprintf("%s & %s", $shellOne, $shellTwo);
        exec($shell, $output, $resultCode);
    }

    public function testBatchCreate01()
    {
        $count1 = ResetOrderModel::count();
        $dataPath = __DIR__.'/data.txt';

        $shellOne = "ab -n 1000 -c 100 -p '{$dataPath}' {$this->urlOne}/resetOrderTest/orderWithLocal";
        $shell = sprintf("%s", $shellOne);
        exec($shell, $output, $resultCode);
        echo $output;
        $count2 = ResetOrderModel::count();

        $this->assertTrue($count2 - $count1 == 1000);

        $this->assertTrue(true);
    }

    public function testBatchCreate02()
    {
        $count1 = ResetOrderModel::count();
        $dataPath = __DIR__.'/data.txt';

        $shellOne = "ab -n 1000 -c 100 -p '{$dataPath}' {$this->urlOne}/resetOrderTest/orderWithRt";
        $shell = sprintf("%s", $shellOne);
        exec($shell, $output, $resultCode);
        echo $output;

        $count2 = ResetOrderModel::count();

        $this->assertTrue($count2 - $count1 == 1000);
    }

    public function testBatchCreate03()
    {
        ResetOrderModel::where('id', '<=', 10)->delete();
        sleep(6);
        $shellOne = "ab -n 100 -c 10 {$this->urlOne}/resetOrderTest/disorderWithLocal";
        $shellTwo = "ab -n 100 -c 10 {$this->urlTwo}/resetOrderTest/disorderWithLocal";

        $shell = sprintf("%s & %s", $shellOne, $shellTwo);
        exec($shell, $output, $resultCode);
    }

    public function testBatchCreate04()
    {
        ResetOrderModel::where('id', '<=', 10)->delete();
        sleep(6);
        $shellOne = "ab -n 100 -c 10 {$this->urlOne}/resetOrderTest/disorderWithRt";
        $shellTwo = "ab -n 100 -c 10 {$this->urlTwo}/resetOrderTest/disorderWithRt";

        $shell = sprintf("%s & %s", $shellOne, $shellTwo);
        exec($shell, $output, $resultCode);
    }

    public function testBatchCreate05()
    {
        ResetOrderModel::truncate();

        $amount1 = ResetAccountModel::where('id', 1)->value('amount');
        $stockQty1 = ResetStorageModel::where('id', 1)->value('stock_qty');

        $dataPath = __DIR__.'/data.txt';
        $shellOne = "ab -n 12 -c 4 -p '{$dataPath}' {$this->urlOne}/resetAccountTest/createOrdersCommit";
        $shellTwo = "ab -n 12 -c 4  -p '{$dataPath}' {$this->urlTwo}/resetAccountTest/createOrdersCommit";

        $shell = sprintf("%s & %s", $shellOne, $shellTwo);
        exec($shell, $output, $resultCode);

        $amount2 = ResetAccountModel::where('id', 1)->value('amount');
        $stockQty2 = ResetStorageModel::where('id', 1)->value('stock_qty');

        $amountSum = ResetOrderModel::sum('amount');
        $stockQtySum = ResetOrderModel::sum('stock_qty');
        $total = ResetOrderModel::count();

        $this->assertTrue($total == 36);
        $this->assertTrue(abs($amount1 - $amount2 - $amountSum) < 0.001);
        $this->assertTrue(($stockQty1 - $stockQty2) == $stockQtySum);

    }
}
