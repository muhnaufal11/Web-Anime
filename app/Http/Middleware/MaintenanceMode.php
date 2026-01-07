<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MaintenanceMode
{
    /**
     * Bypass paths that should always be accessible
     */
    protected array $bypassPaths = [
        'admin',       // Exact match for /admin
        'admin/*',     // Match for /admin/anything
        'livewire/*',
        'api/health',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if maintenance mode is enabled
        $status = Cache::get('web_status', 'online');
        
        if ($status !== 'maintenance') {
            return $next($request);
        }

        // Check bypass paths
        foreach ($this->bypassPaths as $path) {
            if ($request->is($path)) {
                return $next($request);
            }
        }

        // Check bypass cookie (for admins)
        $bypassSecret = Cache::get('maintenance_bypass_secret');
        if ($bypassSecret && $request->cookie('maintenance_bypass') === $bypassSecret) {
            return $next($request);
        }

        // Check if user is authenticated admin
        if (auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())) {
            return $next($request);
        }

        // Check bypass URL
        $bypassSecret = Cache::get('maintenance_bypass_secret');
        if ($bypassSecret && $request->path() === $bypassSecret) {
            // Set bypass cookie and redirect to home
            return redirect('/')
                ->withCookie(cookie('maintenance_bypass', $bypassSecret, 60 * 24)); // 24 hours
        }

        // Show maintenance page
        $maintenanceData = [
            'message' => Cache::get('web_status_message', 'Website sedang dalam maintenance'),
            'estimated_time' => Cache::get('web_status_eta'),
            'updated_at' => Cache::get('web_status_updated_at'),
        ];

        return response()->view('maintenance', $maintenanceData, 503);
    }
}
