<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CacheHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Skip for non-GET requests
        if ($request->method() !== 'GET') {
            return $response;
        }
        
        // Skip for authenticated routes
        if ($request->is('admin/*') || $request->is('auth/*')) {
            return $response;
        }
        
        // Add cache headers for HTML pages
        if ($response->headers->get('Content-Type') === 'text/html; charset=UTF-8') {
            // Cache for 5 minutes for regular pages
            $response->headers->set('Cache-Control', 'public, max-age=300, s-maxage=600');
            
            // Add ETag
            $etag = md5($response->getContent());
            $response->headers->set('ETag', '"' . $etag . '"');
            
            // Check If-None-Match
            $ifNoneMatch = $request->header('If-None-Match');
            if ($ifNoneMatch === '"' . $etag . '"') {
                return response('', 304)->withHeaders([
                    'ETag' => '"' . $etag . '"',
                    'Cache-Control' => 'public, max-age=300, s-maxage=600'
                ]);
            }
        }
        
        return $response;
    }
}
