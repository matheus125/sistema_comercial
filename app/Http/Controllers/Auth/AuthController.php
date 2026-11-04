<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function loginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if ($user && $user->locked_until && $user->locked_until->isFuture()) {
            throw ValidationException::withMessages(['email' => 'Usuário bloqueado temporariamente por tentativas inválidas.']);
        }

        if (!$user || !Hash::check($credentials['password'], $user->password) || $user->status !== 'active') {
            if ($user) {
                $attempts = $user->failed_login_attempts + 1;
                $user->forceFill([
                    'failed_login_attempts' => $attempts,
                    'locked_until' => $attempts >= 5 ? now()->addMinutes(15) : null,
                ])->save();
            }

            Audit::log('login_failed', 'Tentativa inválida para '.$credentials['email']);
            throw ValidationException::withMessages(['email' => 'Credenciais inválidas.']);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $user->forceFill([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        Audit::log('login', 'Login realizado');

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Audit::log('logout', 'Logout realizado');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function forgotForm()
    {
        return view('auth.forgot');
    }

    public function forgot(Request $request)
    {
        $request->validate(['email' => ['required', 'email', 'exists:users,email']]);
        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        Audit::log('password_reset_requested', 'Recuperação solicitada para '.$request->email);

        return back()->with('status', 'Token gerado. Configure e-mail em produção. Link local: '.route('password.reset', ['token' => $token, 'email' => $request->email]));
    }

    public function resetForm(Request $request, string $token)
    {
        return view('auth.reset', ['token' => $token, 'email' => $request->email]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'token' => ['required'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();
        abort_unless($record && Hash::check($request->token, $record->token), 422, 'Token inválido.');

        User::where('email', $request->email)->update(['password' => Hash::make($request->password)]);
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        Audit::log('password_reset_completed', 'Senha redefinida para '.$request->email);

        return redirect()->route('login')->with('status', 'Senha redefinida. Faça login.');
    }

    public function changePasswordForm()
    {
        return view('auth.change-password');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $request->user()->update(['password' => Hash::make($request->password)]);
        Audit::log('password_changed', 'Senha alterada pelo usuário');

        return back()->with('status', 'Senha alterada com sucesso.');
    }
}
