<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class EnsureOtpVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = Auth::user();
        if ($user && is_null($user->email_verified_at)) {
            // Only allow access to OTP verification routes
            if (!($request->routeIs('auth.otp') || $request->routeIs('auth.otp.verify') || $request->routeIs('auth.otp.resend') || $request->routeIs('auth.logout'))) {
                return redirect()->route('auth.otp')->with('error', 'Verifikasi email diperlukan.');
            }
        }
        return $next($request);
    }
}
