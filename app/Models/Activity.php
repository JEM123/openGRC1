<?php

namespace App\Models;

use App\Traits\HasActivityStatus;
use App\Enums\Status;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    use HasActivityStatus;

    protected $casts = [
        'status' => Status::class,
    ];

    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
        'status',
        'event',
        'ip_address',
    ];
} 