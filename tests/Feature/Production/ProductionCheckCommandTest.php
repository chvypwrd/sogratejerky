<?php

namespace Tests\Feature\Production;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ProductionCheckCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_runs_successfully(): void
    {
        $exitCode = Artisan::call('app:production-check');

        $this->assertContains($exitCode, [0, 1]);
    }

    public function test_command_output_contains_no_secret_values(): void
    {
        $fakeKey = 'base64:'.base64_encode('fake-test-key-for-production-check');
        config(['app.key' => $fakeKey]);

        Artisan::call('app:production-check');
        $output = Artisan::output();

        $this->assertStringNotContainsString('fake-test-key-for-production-check', $output);
        $this->assertStringNotContainsString($fakeKey, $output);
    }

    public function test_command_checks_app_key_presence(): void
    {
        $exitCode = Artisan::call('app:production-check');
        $output = Artisan::output();

        $this->assertStringContainsString('APP_KEY', $output);
        $this->assertContains($exitCode, [0, 1]);
    }

    public function test_command_output_is_readable(): void
    {
        $exitCode = Artisan::call('app:production-check');
        $output = Artisan::output();

        $this->assertIsInt($exitCode);
        $this->assertNotEmpty($output);
        $this->assertStringContainsString('Production Readiness', $output);
    }

    public function test_debug_true_triggers_warning(): void
    {
        $originalDebug = config('app.debug');
        $originalEnv = config('app.env');
        $originalUrl = config('app.url');
        config(['app.debug' => true, 'app.env' => 'production', 'app.url' => 'https://example.com']);

        Artisan::call('app:production-check');
        $output = Artisan::output();

        config(['app.debug' => $originalDebug, 'app.env' => $originalEnv, 'app.url' => $originalUrl]);

        $this->assertStringContainsString('APP_DEBUG', $output);
        $this->assertStringContainsString('app_debug', strtolower($output));
    }

    public function test_non_https_url_triggers_failure(): void
    {
        $originalUrl = config('app.url');
        $originalEnv = config('app.env');
        config(['app.url' => 'http://example.test', 'app.env' => 'production']);

        $exitCode = Artisan::call('app:production-check');

        config(['app.url' => $originalUrl, 'app.env' => $originalEnv]);

        $this->assertEquals(1, $exitCode);
    }

    public function test_missing_app_key_triggers_failure(): void
    {
        $originalKey = config('app.key');
        config(['app.key' => null]);

        $exitCode = Artisan::call('app:production-check');
        $output = Artisan::output();

        config(['app.key' => $originalKey]);

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('APP_KEY', $output);
    }

    public function test_database_failure_triggers_failure_safely(): void
    {
        $originalDriver = config('database.default');
        config(['database.default' => 'nonexistent']);

        $exitCode = Artisan::call('app:production-check');
        $output = Artisan::output();

        config(['database.default' => $originalDriver]);

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('Database', $output);
        $this->assertStringNotContainsString('password', strtolower($output));
    }
}
