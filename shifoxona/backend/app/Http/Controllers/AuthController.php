<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function csrf()
    {
        return response()->json(['csrf_token' => csrf_token()]);
    }

    public function login(Request $request)
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        $credentials = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        $key = 'login:'.hash('sha256', $credentials['email'].'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['message' => 'Urinishlar ko‘payib ketdi. '.RateLimiter::availableIn($key).' soniyadan so‘ng qayta urinib ko‘ring.'], 429);
        }
        if (! Auth::attempt($credentials)) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'Email yoki parol noto‘g‘ri.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();

        return response()->json(['user' => $request->user()->load('hospital'), 'csrf_token' => csrf_token()]);
    }

    public function me(Request $request)
    {
        return response()->json(['user' => $request->user()->load('hospital'), 'csrf_token' => csrf_token()]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['csrf_token' => csrf_token()]);
    }
}
