<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerPasswordResetLinkRequest;
use App\Http\Requests\CustomerPasswordResetRequest;
use App\Notifications\CustomerResetPassword;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomerPasswordController extends Controller
{
    public function showForgot(): View
    {
        return view('auth.customer-forgot-password');
    }

    public function sendResetLink(CustomerPasswordResetLinkRequest $request): RedirectResponse
    {
        Password::sendResetLink(
            $request->only('email'),
            fn ($user, $token) => $user->notify(new CustomerResetPassword($token))
        );

        return back()->with('status', 'If an account exists for that email, a password reset link has been sent.');
    }

    public function showReset(Request $request, string $token): View
    {
        return view('auth.customer-reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function reset(CustomerPasswordResetRequest $request): RedirectResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) {
                $user->forceFill([
                    'password' => $password,
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()
                ->route('customer.login')
                ->with('status', 'Your password has been reset. You can sign in now.');
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }
}
