<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use App\Helpers\ValidationHelper;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            $key = 'login-attempts:' . $request->ip();
            if (RateLimiter::tooManyAttempts($key, 5)) {
                return response()->json(['message' => 'Terlalu banyak percobaan login. Coba lagi nanti.'], 429);
            }

            RateLimiter::hit($key, 60);

            $validator = ValidationHelper::validateLogin($request->all());

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $data = $validator->validated();

            $user = User::where('phone', $data['phone'])->first();

            if (!$user || !Hash::check($data['password'], $user->password)) {
                return response()->json(['message' => 'Nomor telepon atau kata sandi salah.'], 401);
            }

            $token = $user->createToken('auth_token', ['*'], now()->addHours(6))->plainTextToken;

            return response()->json([
                'message' => 'Login berhasil.',
                'token' => $token
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'gender' => $user->gender,
            'birthdate' => $user->birthdate,
            'address' => $user->address,
            'role' => $user->role,
            'avatar' => $user->avatar
        ], 200);
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->tokens()->delete();

            return response()->json(['message' => 'Logged out successfully'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
