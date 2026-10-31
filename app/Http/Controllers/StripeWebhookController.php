<?php

namespace App\Http\Controllers;

use App\Services\PaymentReconciler;
use App\Services\StripeGateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Stripe\Exception\SignatureVerificationException;
use Throwable;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeGateway $gateway, PaymentReconciler $reconciler): Response
    {
        try {
            $event = $gateway->constructWebhookEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
            );
        } catch (SignatureVerificationException) {
            return response('Invalid signature', 400);
        } catch (ValidationException) {
            return response('Webhook not configured', 503);
        } catch (Throwable $exception) {
            report($exception);

            return response('Invalid webhook', 400);
        }

        try {
            $reconciler->handleEvent($event);
        } catch (Throwable $exception) {
            report($exception);

            return response('Webhook processing failed', 500);
        }

        return response('ok');
    }
}
