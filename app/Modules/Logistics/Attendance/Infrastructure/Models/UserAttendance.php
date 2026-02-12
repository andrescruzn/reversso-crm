<?php

declare(strict_types=1);

namespace App\Modules\Logistics\Attendance\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserAttendance extends Model
{
    use SoftDeletes;

    protected $table = 'user_attendance';

    protected $fillable = [
        'user_id',
        'check_in',
        'check_out',
        'is_holiday',
        'status',
    ];

    protected $casts = [
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'is_holiday' => 'boolean',
    ];
}
