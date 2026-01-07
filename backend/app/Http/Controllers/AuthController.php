<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (!str_ends_with($request->email, '@polije.ac.id')) {
            return response()->json([
                'message' => 'Gunakan email resmi Polije'
            ], 403);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Email atau password salah'
            ], 401);
        }

        $user = Auth::user();

        return response()->json([
            'token' => $user->createToken('auth_token')->plainTextToken,
            'user' => $user
        ]);
    }
}
