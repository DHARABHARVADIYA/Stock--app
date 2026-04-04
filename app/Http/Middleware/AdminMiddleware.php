<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {

            // Token missing
            if (!$request->bearerToken()) {
                return response()->json([
                    'status'  => 401,
                    'message' => ['Token not provided'],
                    'data'    => null
                ], 401);
            }

            // Invalid token / not logged in
            if (!Auth::guard('api')->check()) {
                return response()->json([
                    'status'  => 401,
                    'message' => ['Invalid token'],
                    'data'    => null
                ], 401);
            }

            $user = Auth::guard('api')->user();

            // Role check
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
