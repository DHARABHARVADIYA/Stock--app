<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'status' => 401,
                'message' => 'Unauthenticated',
                'result' => null
            ], 401);
        }

        // only accounts or admin
        if (!in_array($user->role, ['accounts', 'admin'])) {
            return response()->json([
                'status' => 403,
                'message' => 'Access denied',
                'result' => null
            ], 403);
        }




        return $next($request);
    }
}
