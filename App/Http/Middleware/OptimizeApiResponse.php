<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OptimizeApiResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Add caching headers for GET requests
        if ($request->isMethod('GET')) {
            $response->headers->set('Cache-Control', 'public, max-age=300'); // 5 minutes
        }

        // Enable compression
        if (!$response->headers->has('Content-Encoding')) {
            if (function_exists('gzencode') && strpos($request->header('Accept-Encoding'), 'gzip') !== false) {
                $content = $response->getContent();
                if (strlen($content) > 1024) { // Only compress if > 1KB
                    $compressed = gzencode($content, 6);
                    $response->setContent($compressed);
                    $response->headers->set('Content-Encoding', 'gzip');
                    $response->headers->set('Content-Length', strlen($compressed));
                }
            }
        }

        // Add performance headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');

        return $response;
    }
}
