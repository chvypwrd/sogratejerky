<?php

namespace App\Console\Commands;

use App\Services\CheckoutAvailability;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProductionCheckCommand extends Command
{
    protected $signature = 'app:production-check';

    protected $description = 'Run non-destructive production readiness checks';

    private array $results = [];

    public function handle(): int
    {
        $this->line('');
        $this->info('Production Readiness Checks');
        $this->line(str_repeat('-', 50));

        $this->checkAppEnvironment();
        $this->checkAppDebug();
        $this->checkAppKey();
        $this->checkAppUrl();
        $this->checkDatabase();
        $this->checkStorageWritable();
        $this->checkStorageLink();
        $this->checkBootstrapCacheWritable();
        $this->checkSecureSession();
        $this->checkQueue();
        $this->checkFailedJobs();
        $this->checkMailFrom();
        $this->checkStripeConfiguration();
        $this->checkCheckoutGate();
        $this->checkGoogleVerification();

        $this->line('');
        $this->line(str_repeat('-', 50));

        $failures = array_filter($this->results, fn ($r) => $r === 'fail');

        if (count($failures) > 0) {
            $this->error('FAILED - '.count($failures).' critical issue(s) found');

            return 1;
        }

        $warnings = array_filter($this->results, fn ($r) => $r === 'warn');
        if (count($warnings) > 0) {
            $this->line('<comment>PASSED with '.count($warnings).' warning(s)</comment>');
        } else {
            $this->info('ALL CHECKS PASSED');
        }

        return 0;
    }

    private function recordPass(string $label): void
    {
        $this->results[$label] = 'pass';
        $this->line('<info>  ✓</info> '.$label);
    }

    private function recordWarning(string $label, string $reason = ''): void
    {
        $this->results[$label] = 'warn';
        $msg = '<comment>  ⚠</comment> '.$label;
        if ($reason) {
            $msg .= ' - '.$reason;
        }
        $this->line($msg);
    }

    private function recordFailure(string $label, string $reason = ''): void
    {
        $this->results[$label] = 'fail';
        $msg = '<error>  ✗</error> '.$label;
        if ($reason) {
            $msg .= ' - '.$reason;
        }
        $this->error($msg);
    }

    private function checkAppEnvironment(): void
    {
        if (app()->isProduction()) {
            $this->recordPass('APP_ENV is production');
        } else {
            $this->recordWarning('APP_ENV is not production', app()->environment());
        }
    }

    private function checkAppDebug(): void
    {
        if (config('app.debug')) {
            $this->recordWarning('APP_DEBUG is true', 'should be false in production');
        } else {
            $this->recordPass('APP_DEBUG is false');
        }
    }

    private function checkAppKey(): void
    {
        if (config('app.key')) {
            $this->recordPass('APP_KEY is set');
        } else {
            $this->recordFailure('APP_KEY is not set');
        }
    }

    private function checkAppUrl(): void
    {
        $url = config('app.url');
        if (str_starts_with($url, 'https://')) {
            $this->recordPass('APP_URL uses HTTPS');
        } else {
            $this->recordFailure('APP_URL does not use HTTPS', $url);
        }
    }

    private function checkDatabase(): void
    {
        try {
            DB::select('SELECT 1');
            $this->recordPass('Database connection works');
        } catch (\Throwable $e) {
            $this->recordFailure('Database connection failed');
        }
    }

    private function checkStorageWritable(): void
    {
        $path = storage_path();
        if (is_writable($path)) {
            $this->recordPass('storage/ directory is writable');
        } else {
            $this->recordFailure('storage/ directory is not writable');
        }
    }

    private function checkBootstrapCacheWritable(): void
    {
        $path = base_path('bootstrap/cache');
        if (is_writable($path)) {
            $this->recordPass('bootstrap/cache is writable');
        } else {
            $this->recordFailure('bootstrap/cache is not writable');
        }
    }

    private function checkStorageLink(): void
    {
        if (is_link(public_path('storage')) || file_exists(public_path('storage'))) {
            $this->recordPass('Public storage link exists');
        } else {
            $this->recordFailure('Public storage link is missing', 'run php artisan storage:link');
        }
    }

    private function checkSecureSession(): void
    {
        if (config('session.secure') && config('session.encrypt')) {
            $this->recordPass('Secure encrypted sessions are enabled');
        } else {
            $this->recordWarning('Session security is incomplete', 'set SESSION_SECURE_COOKIE=true and SESSION_ENCRYPT=true');
        }
    }

    private function checkQueue(): void
    {
        $connection = config('queue.default');

        if (! in_array($connection, ['sync', 'null'], true)) {
            $this->recordPass('Queue connection is asynchronous');
        } else {
            $this->recordWarning('Queue connection is synchronous', 'configure a production queue worker');
        }
    }

    private function checkFailedJobs(): void
    {
        try {
            $count = DB::table('failed_jobs')->count();

            if ($count === 0) {
                $this->recordPass('No failed queue jobs');
            } else {
                $this->recordWarning($count.' failed queue job(s) found', 'review with php artisan queue:failed');
            }
        } catch (\Throwable $e) {
            $this->recordWarning('Could not inspect failed queue jobs');
        }
    }

    private function checkStripeConfiguration(): void
    {
        $key = filled(config('services.stripe.key'));
        $secret = filled(config('services.stripe.secret'));
        $webhook = filled(config('services.stripe.webhook_secret'));

        if ($key && $secret && $webhook) {
            $this->recordPass('Stripe keys and webhook secret are configured');
        } elseif ($key || $secret) {
            $this->recordWarning('Stripe configuration is incomplete', 'verify mode, publishable key, secret, and webhook secret');
        } else {
            $this->recordWarning('Stripe is not configured');
        }
    }

    private function checkCheckoutGate(): void
    {
        if (app(CheckoutAvailability::class)->enabled()) {
            $this->recordWarning('Checkout is enabled', 'confirm launch authorization, tax policy, and verified payment delivery');
        } else {
            $this->recordPass('Checkout gate is closed');
        }
    }

    private function checkMailFrom(): void
    {
        $from = config('mail.from.address');
        if ($from && $from !== 'hello@example.com') {
            $this->recordPass('MAIL_FROM_ADDRESS is configured');
        } else {
            $this->recordWarning('MAIL_FROM_ADDRESS is default', 'update for production');
        }
    }

    private function checkGoogleVerification(): void
    {
        try {
            $setting = DB::table('settings')->where('key', 'google_site_verification')->first();
            if ($setting && $setting->value) {
                $this->recordPass('Google verification setting exists');
            } else {
                $this->recordWarning('Google verification setting not found', 'optional');
            }
        } catch (\Throwable $e) {
            $this->recordWarning('Could not check Google verification setting', 'table may not exist');
        }
    }
}
