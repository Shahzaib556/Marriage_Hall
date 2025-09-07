<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    // Register
    public function register(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|unique:users,phone',
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => 'nullable|in:user,owner,admin',
        ];

        // If hall_owner, bank_name and account_number are required
        if ($request->role === 'owner') {
            $rules['bank_name'] = 'required|string|max:255';
            $rules['account_number'] = 'required|string|max:50';
        }

        $data = $request->validate($rules);

        $user = User::create([
            'name'           => $request->name,
            'email'          => $request->email,
            'phone'          => $request->phone,
            'password'       => Hash::make($request->password),
            'role'           => $request->role ?? 'user',
            'bank_name'      => $request->bank_name ?? null,   // ✅ Only stored if hall_owner
            'account_number' => $request->account_number ?? null,
        ]);

        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'user'    => $user,
            'token'   => $token,
        ], 201);
    }

    // Login
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if (isset($user->is_active) && !$user->is_active) {
            return response()->json(['message' => 'Account disabled'], 403);
        }

        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user'    => $user,
            'token'   => $token,
            'role'    => $user->role
        ]);
    }

    // Logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    // Get current user
    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}
