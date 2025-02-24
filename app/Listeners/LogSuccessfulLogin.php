<?php

namespace App\Listeners;

use App\Enums\Status;
use Illuminate\Auth\Events\Login;
use Spatie\Activitylog\Facades\LogBatch;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Facades\CauserResolver;

class LogSuccessfulLogin
{
    public function handle(Login $event): void
    {
        $activity = activity()
            ->performedOn($event->user)
            ->withProperties([
                'user_agent' => request()->userAgent(),
                'email' => $event->user->email,
            ])
            ->event('login')
            ->log('User logged in successfully');
            
        $activity->status = Status::SUCCESS->value;
        $activity->ip_address = request()->ip();
        $activity->save();
    }
} 