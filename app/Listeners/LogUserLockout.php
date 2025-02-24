<?php

namespace App\Listeners;

use App\Events\UserLockedOut;
use App\Enums\Status;

class LogUserLockout
{
    public function handle(UserLockedOut $event): void
    {
        $activity = activity()
            ->performedOn($event->user)
            ->withProperties([
                'user_agent' => request()->userAgent(),
                'email' => $event->email,
            ])
            ->event('login_lockout')
            ->log('User account locked due to too many failed login attempts');
            
        $activity->status = Status::FAILURE->value;
        $activity->ip_address = request()->ip();
        $activity->save();
    }
} 