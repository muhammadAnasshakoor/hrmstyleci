<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    public function handle($request, Closure $next, $permission)
    {
        // Check if the user is authenticated
        // if (Auth::check()) {
            // Check if the user has the required permission
            if (Auth::user()->hasPermissionTo($permission)) {
                return $next($request);
            } else {
                // If the user does not have the required permission, return a 403 Forbidden error
                return response()->json(['error' => 'Forbidden', 'message' => 'You do not have permission to perform this action.'], 403);
            }
        // } else {
            // If the user is not authenticated, return a 401 Unauthorized error
            // return response()->json(['error' => 'Unauthorized', 'message' => 'You are not authenticated.'], 401);
        // }
    }
}


