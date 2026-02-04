<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\ManagementLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('management/Login');
    }

    public function store(ManagementLoginRequest $request): RedirectResponse
    {
        $credentials = [
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
            'is_active' => true,
        ];

        if (! Auth::guard('management')->attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'Credenciais inválidas.',
            ]);
        }

        $request->session()->regenerate();
        $user = Auth::guard('management')->user();

        if ($user) {
            $user->forceFill([
                'last_login_at' => now(),
            ])->save();
        }

        return redirect()->route('management.dashboard');
    }

    public function destroy(): RedirectResponse
    {
        Auth::guard('management')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('management.login');
    }
}
