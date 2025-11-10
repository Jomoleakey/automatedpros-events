<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $fields = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|confirmed|min:8',
            'role' => ['nullable', Rule::in(['admin', 'organizer', 'customer'])], // Allow setting role during registration
        ]);

        $user = User::create([
            'name' => $fields['name'],
            'email' => $fields['email'],
            'password' => Hash::make($fields['password']),
            'role' => $fields['role'] ?? 'customer', // Default to customer if not provided
        ]);

        $token = $user->createToken('myapptoken')->plainTextToken;

        return response([
            'user' => $user,
            'token' => $token
        ], 201);
    }

    public function login(Request $request)
    {
        $fields = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        // Attempt to log in the user
        if (!Auth::attempt($fields)) {
            return response([
                'message' => 'Invalid login credentials.'
            ], 401);
        }

        $user = Auth::user();

        // Check if the user object is valid before creating token
        if ($user) {
            // Revoke any existing tokens for cleaner state
            $user->tokens()->delete();

            $token = $user->createToken('myapptoken')->plainTextToken;
            return response([
                'user' => $user,
                'token' => $token
            ], 200);
        }

        return response([
            'message' => 'Login failed.'
        ], 401);
    }

    public function logout(Request $request)
    {
        // Delete the current access token used for the request
        $request->user()->currentAccessToken()->delete();

        return response([
            'message' => 'Logged out successfully.'
        ], 200);
    }
}