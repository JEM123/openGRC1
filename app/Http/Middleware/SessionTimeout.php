<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class SessionTimeout
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $timeout = setting('security.session_timeout', 15) * 60;
        $user = auth()->user();

        // Get current last_activity directly from database
        $currentActivity = DB::table('users')
            ->where('id', $user->id)
            ->value('last_activity');      

        // If the user has been inactive for longer than the timeout, log them out.
        if ($currentActivity && strtotime($currentActivity) + $timeout < now()->timestamp) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            // Check if this is an AJAX/Livewire request
            if ($request->expectsJson() || $request->header('X-Livewire')) {
                return response()->json(['redirect' => route('filament.app.auth.login')], 401);
            }
            
            return redirect()->route('filament.app.auth.login')->with('message', 'Your session has expired due to inactivity.');
        }

        // Update last_activity for regular requests, but not for logout or session timeout actions
        $isLogoutRequest = $request->header('X-Livewire') && 
                          (str_contains($request->getContent(), 'logout') || 
                           str_contains($request->getPathInfo(), 'session-timeout-warning'));
        
        if (!$isLogoutRequest) {
            DB::table('users')
                ->where('id', $user->id)
                ->update(['last_activity' => now()]);
        }

        return $next($request);
    }
}
