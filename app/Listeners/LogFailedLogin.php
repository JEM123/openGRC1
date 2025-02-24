<?php

namespace App\Listeners;

use App\Enums\Status;
use Illuminate\Auth\Events\Failed;
use Spatie\Activitylog\Facades\LogBatch;
use Spatie\Activitylog\LogOptions;

class LogFailedLogin
{
    public function handle(Failed $event): void
    {
        $activity = activity()
            ->withProperties([
                'user_agent' => request()->userAgent(),
                'email' => $event->credentials['email'] ?? 'unknown',
            ])
            ->event('login_failed')
            ->log('Failed login attempt');
            
        $activity->status = Status::FAILURE->value;
        $activity->ip_address = request()->ip();
        $activity->save();
    }
} 