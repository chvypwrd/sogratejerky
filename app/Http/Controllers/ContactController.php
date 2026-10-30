<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactMessageRequest;
use App\Models\ContactMessage;
use App\Notifications\NewContactMessageNotification;
use App\Services\ContactSpamGuard;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(): View
    {
        session(['contact_form_start' => now()->timestamp]);

        $settings = app(SettingsService::class);

        return view('contact.index', [
            'settings' => $settings,
            'timingToken' => Str::random(40),
        ]);
    }

    public function store(StoreContactMessageRequest $request, ContactSpamGuard $spamGuard): RedirectResponse
    {
        $spamResult = $spamGuard->handle($request);

        if (! $spamResult['passed']) {
            return redirect()->route('contact.show')->with('status', 'Thank you for your message.');
        }

        $data = $request->validated();

        $message = ContactMessage::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'subject' => $data['subject'] ?? null,
            'message' => $data['message'],
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit($request->userAgent(), 1000),
        ]);

        session()->forget('contact_form_start');

        $settings = app(SettingsService::class);
        $notificationEmail = $settings->get('contact.notification_email')
            ?: $settings->get('contact.email')
            ?: config('mail.from.address');

        if ($notificationEmail) {
            try {
                Notification::route('mail', $notificationEmail)
                    ->notify(new NewContactMessageNotification($message));
            } catch (\Throwable $e) {
                \Log::warning('Contact notification failed', [
                    'contact_message_id' => $message->id,
                    'exception' => get_class($e),
                ]);
            }
        }

        return redirect()->route('contact.show')
            ->with('status', 'Thank you for your message. We will get back to you soon.');
    }
}
