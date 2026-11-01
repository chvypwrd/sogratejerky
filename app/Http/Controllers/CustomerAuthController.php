<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerLoginRequest;
use App\Http\Requests\RegisterCustomerRequest;
use App\Models\User;
use App\Notifications\CustomerVerifyEmail;
use App\Services\LoginRedirectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomerAuthController extends Controller
{
    public function __construct(
        private LoginRedirectService $loginRedirects,
    ) {}

    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->to($this->loginRedirects->defaultDestination(Auth::user()));
        }

        return view('auth.customer-login');
    }

    public function login(CustomerLoginRequest $request): RedirectResponse
    {
        $credentials = [
            'email' => Str::lower((string) $request->input('email')),
            'password' => $request->input('password'),
        ];

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        $request->session()->regenerate();

        return $this->loginRedirects->response($request);
    }

    public function showRegister(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->to($this->loginRedirects->defaultDestination(Auth::user()));
        }

        return view('auth.customer-register');
    }

    public function register(RegisterCustomerRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => Str::lower($data['email']),
            'password' => $data['password'],
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        try {
            $user->notify(new CustomerVerifyEmail);
        } catch (\Throwable $e) {
            Log::warning('Customer verification notification failed', [
                'user_id' => $user->id,
                'exception' => get_class($e),
            ]);
        }

        return redirect()
            ->route('account.dashboard')
            ->with('status', 'Your account has been created. Check your email to verify your address.');
    }

    public function logout(): RedirectResponse
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('home');
    }
}
