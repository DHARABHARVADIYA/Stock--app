<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\RefreshToken;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AuthController extends Controller
{
    /* ===================== LOGIN ===================== */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile_number' => 'required',
            'password'      => 'required'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->all(), 'Validation error', 422);
        }

        $user = User::where('mobile_number', $request->mobile_number)
            ->where('is_active', 1)
            ->first();

        if (!$user || $user->password !== $request->password) {
            return $this->errorResponse(null, 'Invalid credentials', 401);
        }

        $accessToken = auth('api')->login($user);

        $user = auth('api')->user();

        $refreshToken = Str::random(64);

        RefreshToken::create([
            'user_id' => $user->id,
            'refresh_token' => $refreshToken,
            'expires_at' => Carbon::now()->addDays(30),
        ]);

        return $this->successResponse([
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type'    => 'bearer',
            'expires_in'    => auth('api')->factory()->getTTL() * 60,
            'user'          => $user
        ], 'Login successful');
    }

    /* ===================== REFRESH TOKEN ===================== */
    public function refresh(Request $request)
    {
        $request->validate([
            'refresh_token' => 'required'
        ]);

        $refresh = RefreshToken::where('refresh_token', $request->refresh_token)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$refresh) {
            return $this->errorResponse(null, 'Invalid or expired refresh token', 401);
        }

        $user = User::find($refresh->user_id);

        if (!$user || !$user->is_active) {
            return $this->errorResponse(null, 'User inactive', 401);
        }

        $accessToken = auth('api')->login($user);

        $refresh->update([
            'expires_at' => Carbon::now()->addDays(30),
        ]);

        return $this->successResponse([
            'access_token'  => $accessToken,
            'refresh_token' => $request->refresh_token,
            'token_type'    => 'bearer',
            'expires_in'    => auth('api')->factory()->getTTL() * 60
        ], 'Token refreshed successfully');
    }

    /* ===================== REGISTER USER ===================== */
    public function registerUser(Request $request)
    {
        $admin = auth('api')->user();

        if (!$admin) {
            return $this->errorResponse(null, 'Invalid token', 401);
        }

        if ($admin->role !== 'admin') {
            return $this->errorResponse(null, 'Only admin can create or update users', 403);
        }

        $id = $request->id ?? 0;

        $allowedRoles = ['admin', 'sales', 'manager', 'dispatcher', 'accounts'];

        $validator = Validator::make($request->all(), [
            'id'            => 'nullable|integer|min:0',
            'name'          => 'required|string|max:255',

            'mobile_number' => 'required|string|max:20|unique:users,mobile_number,' . ($id ?: 'NULL') . ',id,business_code,' . $admin->business_code,

            'password'      => $id ? 'nullable|string|min:6' : 'required|string|min:6',

            'email'         => 'nullable|email|unique:users,email,' . ($id ?: 'NULL') . ',id,business_code,' . $admin->business_code,

            'gst_number'    => 'nullable|string|max:50',
            'address'       => 'required|string|max:255',
            'is_active'     => 'required|boolean',
            'role'          => 'required|in:' . implode(',', $allowedRoles),
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->all(), 'Validation error', 422);
        }

        if ($id == 0) {

            $user = new User();
            $user->password = $request->password;
            $message = 'User registered successfully';

        } else {

            $user = User::where('id', $id)
                ->where('business_code', $admin->business_code)
                ->first();

            if (!$user) {
                return $this->errorResponse(null, 'User not found', 404);
            }

            if ($request->filled('password')) {
                $user->password = $request->password;
            }

            $message = 'User updated successfully';
        }

        $user->name          = $request->name;
        $user->mobile_number = $request->mobile_number;
        $user->email         = $request->email;
        $user->gst_number    = $request->gst_number;
        $user->address       = $request->address;
        $user->is_active     = $request->is_active;
        $user->role          = $request->role;

        $user->business_code = $admin->business_code;
        $user->business_name = $admin->business_name;
        $user->created_by    = $admin->id;

        $user->save();

        return $this->successResponse($user, $message);
    }

    /* ===================== USER LIST ===================== */
  public function userList(Request $request)
{
    try {
        $admin = auth('api')->user();
    } catch (\Exception $e) {
        return response()->json([
            'status' => 401,
            'message' => ['Invalid token'],
            'result' => (object)[]
        ], 401);
    }

    if ($admin->role !== 'admin') {
        return response()->json([
            'status' => 403,
            'message' => ['Only admin can view users'],
            'result' => (object)[]
        ], 403);
    }

    $validator = Validator::make($request->all(), [
        'role' => 'required|string'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 422,
            'message' => $validator->errors()->all(),
            'result' => (object)[]
        ], 422);
    }

    $role = $request->role;

    $users = User::where('created_by', $admin->id)
        ->where('role', $role)
        ->orderBy('id', 'desc')
        ->get();


    $message = ucfirst($role) . ' list fetched successfully';

    return response()->json([
        'status' => 200,
        'message' => [$message],
        'result' => $users
    ]);
}



    public function deleteUser(Request $request)
{
    $admin = auth('api')->user();

    if (!$admin) {
        return response()->json([
            'status' => 401,
            'message' => ['Invalid token'],
            'result' => (object)[]
        ], 401);
    }

    if ($admin->role !== 'admin') {
        return response()->json([
            'status' => 403,
            'message' => ['Only admin can delete users'],
            'result' => (object)[]
        ], 403);
    }

    $validator = Validator::make($request->all(), [
        'user_id' => 'required|integer|exists:users,id'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 422,
            'message' => $validator->errors()->all(),
            'result' => (object)[]
        ], 422);
    }

    $user = User::where('id', $request->user_id)
        ->where('business_code', $admin->business_code)
        ->first();

    if (!$user) {
        return response()->json([
            'status' => 404,
            'message' => ['User not found'],
            'result' => (object)[]
        ], 404);
    }

    // Prevent admin from deleting himself
    if ($user->id == $admin->id) {
        return response()->json([
            'status' => 400,
            'message' => ['You cannot delete yourself'],
            'result' => (object)[]
        ], 400);
    }

    $user->delete();

    return response()->json([
        'status' => 200,
        'message' => ['User deleted successfully'],
        'result' => (object)[]
    ]);
}

    /* ===================== COMMON RESPONSE ===================== */

    protected function successResponse($result, $message = 'Success', $status = 200)
    {
        return response()->json([
            'status'  => $status,
            'message' => [(string) $message],
            'result'  => $result ?? (object)[]
        ], $status);
    }

    protected function errorResponse($result, $message = 'Error', $status = 400)
    {
        return response()->json([
            'status'  => $status,
            'message' => [(string) $message],
            'result'  => $result ?? (object)[]
        ], $status);
    }
}
