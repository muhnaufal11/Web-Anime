<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureOtpVerified
{
    public function handle(Request $request, Closure $next)
    {
        // 1. Cek apakah ada user yang login?
        if (Auth::check()) {
            $user = Auth::user();

            // [LOGIKA BARU] VIP PASS: 
            // Kalau kolom email_verified_at SUDAH TERISI, langsung loloskan!
            // Jangan dicek lagi, biarkan dia akses apapun.
            if ($user->email_verified_at !== null) {
                return $next($request);
            }

            // 2. Kalau sampai sini, berarti email_verified_at MASIH KOSONG (User Lama/Unverified).
            // Kita cek apakah dia sedang mengakses rute verifikasi?
            $allowedRoutes = [
                'auth.otp', 
                'auth.otp.verify', 
                'auth.otp.resend', 
                'auth.logout'
            ];

            // 3. Kalau dia mencoba kabur ke rute lain (misal: home), TENDANG BALIK ke OTP!
            if (!in_array($request->route()->getName(), $allowedRoutes)) {
                return redirect()->route('auth.otp')->with('error', 'Akun lama terdeteksi. Mohon verifikasi email dulu.');
            }
        }

        return $next($request);
    }
}