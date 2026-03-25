<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckActiveUser
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'status' => 401,
                'message' => 'Unauthenticated',
                'result' => null
            ], 401);
        }

        if ($user->is_active == 0) {
            return response()->json([
                'status' => 403,
                'message' => 'Your account has been deactivated. Please contact admin.',
                'result' => null
            ], 403);
        }

        return $next($request);
    }
}
