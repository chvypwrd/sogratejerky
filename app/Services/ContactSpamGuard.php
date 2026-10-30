<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ContactSpamGuard
{
    public function checkHoneypot(Request $request): bool
    {
        return $request->has('website') && $request->input('website') !== '';
    }

    public function checkMinimumTime(Request $request): bool
    {
        $sessionKey = 'contact_form_start';
        $loadedAt = $request->session()->get($sessionKey);

        if (! $loadedAt) {
            return false;
        }

        $elapsed = now()->timestamp - Carbon::parse($loadedAt)->timestamp;

        return $elapsed < 2;
    }

    public function checkDuplicate(string $email, string $subject, string $message): bool
    {
        $fingerprint = $this->makeFingerprint($email, $subject, $message);

        return Cache::has("contact_duplicate:{$fingerprint}");
    }

    public function recordFingerprint(string $email, string $subject, string $message): void
    {
        $fingerprint = $this->makeFingerprint($email, $subject, $message);

        Cache::put("contact_duplicate:{$fingerprint}", true, now()->addMinutes(5));
    }

    public function handle(Request $request): array
    {
        if ($this->checkHoneypot($request)) {
            return ['passed' => false, 'reason' => 'honeypot'];
        }

        if ($this->checkMinimumTime($request)) {
            return ['passed' => false, 'reason' => 'minimum_time'];
        }

        $email = $request->input('email', '');
        $subject = $request->input('subject', '');
        $message = $request->input('message', '');

        if ($this->checkDuplicate($email, $subject, $message)) {
            return ['passed' => false, 'reason' => 'duplicate'];
        }

        $this->recordFingerprint($email, $subject, $message);

        return ['passed' => true, 'reason' => null];
    }

    protected function makeFingerprint(string $email, string $subject, string $message): string
    {
        $payload = strtolower(trim($email)).'|'.strtolower(trim($subject)).'|'.strtolower(trim($message));

        return hash_hmac('sha256', $payload, config('app.key'));
    }
}
