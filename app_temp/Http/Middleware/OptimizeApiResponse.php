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
        try {
            $response = $next($request);

            // Add caching headers for GET requests
            if ($request->isMethod('GET')) {
                $response->headers->set('Cache-Control', 'public, max-age=300'); // 5 minutes
            }

            // Enable compression - with safety checks
            if (!$response->headers->has('Content-Encoding')) {
                $acceptEncoding = $request->header('Accept-Encoding', '');
                if (function_exists('gzencode') && 
                    is_string($acceptEncoding) && 
                    strpos($acceptEncoding, 'gzip') !== false) {
                    
                    $content = $response->getContent();
                    if ($content !== false && is_string($content) && strlen($content) > 1024) {
                        $compressed = @gzencode($content, 6);
                        if ($compressed !== false) {
                            $response->setContent($compressed);
                            $response->headers->set('Content-Encoding', 'gzip');
                            $response->headers->set('Content-Length', strlen($compressed));
                        }
                    }
                }
            }

            // Add performance headers
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('X-Frame-Options', 'DENY');

            return $response;
            
        } catch (\Throwable $e) {
            // If anything fails, just return the original response
            return $next($request);
        }
    }
}
