<?php

namespace App\Http\Controllers;

use App\Services\StripeGateway;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class CustomerPaymentMethodController extends Controller
{
    public function index(Request $request, StripeGateway $gateway): View
    {
        $methods = [];
        $error = null;

        if ($gateway->configured()) {
            try {
                $methods = $gateway->listSavedPaymentMethods($request->user());
            } catch (Throwable $exception) {
                report($exception);
                $error = 'Saved payment methods could not be loaded right now.';
            }
        } else {
            $error = 'Saved payment methods are not available until online payment is configured.';
        }

        return view('account.payment-methods.index', [
            'methods' => $methods,
            'error' => $error,
        ]);
    }

    public function destroy(Request $request, string $paymentMethod, StripeGateway $gateway): RedirectResponse
    {
        try {
            $gateway->detachSavedPaymentMethod($request->user(), $paymentMethod);
        } catch (AuthorizationException) {
            abort(403);
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'The payment method could not be removed.');
        }

        return back()->with('status', 'Payment method removed.');
    }
}
