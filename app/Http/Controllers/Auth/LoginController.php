<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Display the login form
     */
    public function showLoginForm()
    {
        // Redirect if already authenticated
        if (Auth::check()) {
            return redirect()->intended('/dashboard');
        }

        return view('auth.login');
    }

    /**
     * Handle login attempt
     */
    public function login(Request $request)
    {
        // Validate input
        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        // Check rate limiting
        $this->checkTooManyFailedAttempts($request);

        // Attempt authentication
        if ($this->attemptLogin($request)) {
            // Clear rate limiter on success
            RateLimiter::clear($this->throttleKey($request));

            // Regenerate session to prevent fixation
            $request->session()->regenerate();

            return redirect()->intended('/dashboard');
        }

        // Increment failed attempts
        RateLimiter::hit($this->throttleKey($request), 60);

        // Authentication failed
        throw ValidationException::withMessages([
            'login' => [__('Las credenciales proporcionadas no son correctas.')],
        ]);
    }

    /**
     * Attempt to log the user in
     */
    protected function attemptLogin(Request $request): bool
    {
        $login = $request->input('login');
        $password = $request->input('password');
        $remember = $request->boolean('remember');

        // Find user by username or email
        $user = User::findByUsernameOrEmail($login);

        // Check if user exists
        if (!$user) {
            return false;
        }

        // Check if user is active
        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'login' => [__('Su cuenta ha sido desactivada. Contacte al administrador.')],
            ]);
        }

        // Verify password
        if (!Hash::check($password, $user->password)) {
            return false;
        }

        // Log the user in
        Auth::login($user, $remember);

        return true;
    }

    /**
     * Log the user out
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Check for too many failed login attempts
     */
    protected function checkTooManyFailedAttempts(Request $request): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'login' => [__('Demasiados intentos de inicio de sesión. Por favor intente nuevamente en :seconds segundos.', [
                'seconds' => $seconds,
            ])],
        ]);
    }

    /**
     * Get the rate limiting throttle key
     */
    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->input('login')).'|'.$request->ip());
    }
}
