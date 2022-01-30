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

class ResetStorageModel extends Model
{
    const CREATED_AT = null;
    const UPDATED_AT = null;

    protected $connection = 'service_storage';
    protected $primaryKey = 'id';
    protected $table = 'reset_storage';
    public $timestamps = false;

    protected $fillable = [
        'stock_qty',
    ];
}
