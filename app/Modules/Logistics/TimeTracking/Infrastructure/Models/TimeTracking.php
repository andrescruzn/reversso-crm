<?php

declare(strict_types=1);

namespace App\Modules\Logistics\TimeTracking\Infrastructure\Models;

use App\Common\Enums\TrackingStatus;
use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimeTracking extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'time_tracking';

    protected $fillable = [
        'user_id',
        'start_time',
        'end_time',
        'origin',
        'destination',
        'vehicle_plate', // ✅ CRÍTICO: Debe estar aquí para guardar
        'start_odometer',
        'end_odometer',
        'is_holiday',
        'observations',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'start_odometer' => 'integer',
            'end_odometer' => 'integer',
            'is_holiday' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
