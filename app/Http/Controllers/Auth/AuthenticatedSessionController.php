<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $request->authenticate();

        if (!$request->wantsJson() && !$request->is('api/*')) {
            $request->session()->regenerate();
        }

        if ($request->wantsJson() || $request->is('api/*')) {
            $user = $request->user();
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Logged in successfully',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                ],
            ]);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Get the authenticated user.
     */
    public function me(Request $request): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $request->user(),
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        Auth::guard('web')->logout();

        if (!$request->wantsJson() && !$request->is('api/*')) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Logged out successfully']);
        }

        return redirect('/');
    }
}
