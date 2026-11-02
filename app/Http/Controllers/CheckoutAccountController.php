<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Notifications\CustomerVerifyEmail;
use App\Services\GuestOrderClaimService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Throwable;

class CheckoutAccountController extends Controller
{
    public function store(
        Request $request,
        Order $order,
        GuestOrderClaimService $guestOrderClaims,
    ): RedirectResponse {
        if (Auth::check() || ! $guestOrderClaims->allows($order)) {
            abort(403);
        }

        $email = strtolower(trim((string) $order->customer_email));

        if (User::where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'An account already exists for this email address. Please sign in instead.',
            ]);
        }

        $data = $request->validate([
            'password' => ['required', 'string', Password::default(), 'confirmed'],
        ]);

        $user = User::create([
            'name' => $order->customer_name,
            'email' => $email,
            'password' => $data['password'],
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        $order->update(['user_id' => $user->id]);
        $guestOrderClaims->forget();

        try {
            $user->notify(new CustomerVerifyEmail);
        } catch (Throwable $exception) {
            Log::warning('Customer verification notification failed', [
                'user_id' => $user->id,
                'exception' => get_class($exception),
            ]);
        }

        return redirect()
            ->route('account.orders.show', $order)
            ->with('status', 'Your account has been created. Check your email to verify your address.');
    }
}
