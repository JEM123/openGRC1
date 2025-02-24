<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use App\Models\Activity as CustomActivity;
use App\Enums\Status;

class LogSuccessfulLogout
{
    // public function handle(Logout $event): void
    // {
    //     if ($event->user) {
    //         activity()
    //             ->causedBy($event->user)
    //             ->event('logout')
    //             ->withProperties([
    //                 'email' => $event->user->email,
    //                 'name' => $event->user->name,
    //                 'status' => Status::SUCCESS->value,
    //             ])
    //             ->log('User logged out successfully');
    //     }
    // }

    public function handle(Logout $event): void
    {
        $activity = activity()
            ->performedOn($event->user)
            ->withProperties([
                'user_agent' => request()->userAgent(),
                'email' => $event->user->email,
            ])
            ->event('logout')
            ->log('User logged out successfully');
            
        $activity->status = Status::SUCCESS->value;
        $activity->ip_address = request()->ip();
        $activity->save();
    }


} 