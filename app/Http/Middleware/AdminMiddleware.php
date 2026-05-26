<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {

            if (!$request->bearerToken()) {
                return response()->json([
                    'status'  => 401,
                    'message' => ['Token not provided'],
                    'data'    => null
                ], 401);
            }

            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'status'  => 401,
                    'message' => ['Unauthenticated'],
                    'data'    => null
                ], 401);
            }

            if ($user->role !== 'admin') {
                return response()->json([
                    'status'  => 403,
                    'message' => ['Only admin allowed'],
                    'data'    => null
                ], 403);
            }

        } catch (TokenExpiredException $e) {
            return response()->json([
                'status'  => 401,
                'message' => ['Token expired'],
                'data'    => null
            ], 401);

        } catch (TokenInvalidException $e) {
            return response()->json([
                'status'  => 401,
                'message' => ['Token invalid'],
                'data'    => null
            ], 401);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 401,
                'message' => ['Unauthorized'],
                'data'    => null
            ], 401);
        }

        return $next($request);
    }
}