<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureOtpVerified
{
    public function handle(Request $request, Closure $next)
    {
        // 1. Cek apakah user SUDAH login?
        if (Auth::check()) {
            $user = Auth::user();

            // 2. Cek apakah emailnya BELUM diverifikasi?
            if (is_null($user->email_verified_at)) {
                
                // 3. Daftar rute yang BOLEH diakses saat belum verifikasi
                // (Halaman input OTP, Proses Verifikasi, Kirim Ulang, dan LOGOUT)
                $allowedRoutes = [
                    'auth.otp', 
                    'auth.otp.verify', 
                    'auth.otp.resend', 
                    'auth.logout' // <--- PENTING: Biar user gak terjebak selamanya
                ];

                // 4. Kalau user mencoba kabur ke rute lain (misal: home/search), TENDANG BALIK!
                if (!in_array($request->route()->getName(), $allowedRoutes)) {
                    return redirect()->route('auth.otp')->with('error', 'Eits! Verifikasi email dulu ya.');
                }
            }
        }

        return $next($request);
    }
}