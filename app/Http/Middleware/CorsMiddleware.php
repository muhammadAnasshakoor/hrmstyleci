<?php

namespace App\Http\Middleware;

use Closure;

class CorsMiddleware
{
    public function handle($request, Closure $next)
    {

        $response = $next($request);
        // Allow any origin
        $response->headers->set('Access-Control-Allow-Origin', '*');

        // Allow the following methods
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');

        // Allow the following headers
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');

        // Handle preflight requests
        if ($request->isMethod('OPTIONS')) {
            $response->headers->set('Access-Control-Max-Age', '86400'); // 24 hours
            $response->setStatusCode(200);
        }

        if ($request->is('api/documentation*')) {
            return $response;
        }
        return $response;
    }
}
