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

class ResetAccountModel extends Model
{
    const CREATED_AT = null;
    const UPDATED_AT = null;

    protected $connection = 'service_account';
    protected $primaryKey = 'id';
    protected $table = 'reset_account';
    public $timestamps = false;

    protected $fillable = [
        'amount',
    ];
}
