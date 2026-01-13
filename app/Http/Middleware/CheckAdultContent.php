<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAdultContent
{
    /**
     * Handle an incoming request.
     * Block access to adult content for users under 18.
     */
    public function handle(Request $request, Closure $next)
    {
        // Get the episode from route
        $episode = $request->route('episode');
        
        if (!$episode) {
            return $next($request);
        }

        // Load anime if not loaded
        $anime = $episode->anime;
        
        if (!$anime) {
            return $next($request);
        }

        // Check if anime is adult content
        if (!$anime->isAdultContent()) {
            return $next($request);
        }

        // Adult content - check user
        $user = auth()->user();

        // Not logged in
        if (!$user) {
            return redirect()->route('auth.login')
                ->with('error', 'Konten ini hanya untuk pengguna 18+. Silakan login terlebih dahulu.');
        }

        // User doesn't have birth date
        if (!$user->birth_date) {
            return redirect()->route('profile.show')
                ->with('error', 'Untuk mengakses konten 18+, lengkapi tanggal lahir di profil Anda.');
        }

        // User is under 18
        if (!$user->isAdult()) {
            return redirect()->back()
                ->with('error', 'Konten ini hanya untuk pengguna 18 tahun ke atas.');
        }

        return $next($request);
    }
}
