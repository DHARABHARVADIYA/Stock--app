<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthOnly
{
    public function handle(Request $request, Closure $next)
    {
        try {

            // 1️⃣ token present che ke nahi
            if (!$request->bearerToken()) {
                return response()->json([
                    'status'  => 401,
                    'message' => 'Token not provided',
                    'result'  => null
                ], 401);
            }

            // 2️⃣ token validate + user authenticate
            if (!Auth::guard('api')->check()) {
                return response()->json([
                    'status'  => 401,
                    'message' => 'Invalid token',
                    'result'  => null
                ], 401);
            }

        } catch (TokenExpiredException $e) {

            return response()->json([
                'status'  => 401,
                'message' => 'Token expired',
                'result'  => null
            ], 401);

        } catch (TokenInvalidException $e) {

            return response()->json([
                'status'  => 401,
                'message' => 'Token invalid',
                'result'  => null
            ], 401);

        } catch (\Exception $e) {

            return response()->json([
                'status'  => 401,
                'message' => 'Unauthorized',
                'result'  => null
            ], 401);
        }

        return $next($request);
    }
}
