<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;

class DispatcherMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {

            $user = JWTAuth::parseToken()->authenticate();

        } catch (TokenExpiredException $e) {

            return response()->json([
                'status' => 401,
                'message' => 'Token expired',
                'result' => null
            ]);

        } catch (TokenInvalidException $e) {

            return response()->json([
                'status' => 401,
                'message' => 'Token invalid',
                'result' => null
            ]);

        } catch (JWTException $e) {

            return response()->json([
                'status' => 401,
                'message' => 'Token not provided',
                'result' => null
            ]);
        }

        if (!in_array($user->role, ['dispatcher', 'admin'])) {
            return response()->json([
                'status' => 403,
                'message' => 'Access denied. Only dispatcher or admin allowed.',
                'result' => null
            ]);
        }

        return $next($request);
    }
}
