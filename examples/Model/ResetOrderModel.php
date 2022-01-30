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

namespace App\Model;

use App\Model\Model;

class ResetOrderModel extends Model
{
    const CREATED_AT = null;
    const UPDATED_AT = null;

    protected $connection = 'service_order';
    protected $primaryKey = 'id';
    protected $table = 'reset_order';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'order_no',
        'stock_qty',
        'amount',
        'status',
    ];
}
