<?php

namespace App\Http\Middleware;

use App\Models\TokenBlacklist;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticateStudent
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Get Bearer token
        $token = $request->bearerToken();

        // Check blacklist
        if ($token && TokenBlacklist::where('token', $token)->exists()) {
            return response()->json([], 401);
        }

        // Check Student authentication
        if (!Auth::guard('student')->check()) {
            return response()->json([], 401);
        }

        return $next($request);
    }
}
