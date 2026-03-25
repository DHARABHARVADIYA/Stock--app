<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => 401,
                'message' => 'Unauthorized'
            ], 401);
        }

        if (!in_array($user->role, $roles)) {
            return response()->json([
                'status' => 403,
                'message' => 'Access denied'
            ], 403);
        }

        return $next($request);
    }
}
