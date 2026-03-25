<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SalesMiddleware
{

    public function handle(Request $request, Closure $next)
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'status' => 401,
                'message' => 'Unauthenticated',
                'result' => null
            ]);
        }


        if (!in_array($user->role, ['sales', 'admin'])) {
            return response()->json([
                'status' => 403,
                'message' => 'Access denied. Only sales or admin users allowed.',
                'result' => null
            ]);
        }


        return $next($request);
    }
}
