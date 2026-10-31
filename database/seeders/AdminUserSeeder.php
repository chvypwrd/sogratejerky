<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use RuntimeException;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = strtolower(trim((string) env('ADMIN_SEED_EMAIL')));
        $password = (string) env('ADMIN_SEED_PASSWORD');
        $name = trim((string) env('ADMIN_SEED_NAME')) ?: Str::headline(Str::before($email, '@'));

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);

        if ($validator->fails()) {
            throw new RuntimeException(
                'Admin seeding requires a valid ADMIN_SEED_EMAIL and a strong ADMIN_SEED_PASSWORD. '
                .'Provide both values, run the seeder, then remove them from the environment.'
            );
        }

        $role = Role::query()
            ->where('name', 'Super Admin')
            ->where('guard_name', 'web')
            ->first();

        if (! $role) {
            throw new RuntimeException('The Super Admin role is missing. Run RolePermissionSeeder before AdminUserSeeder.');
        }

        if (! $role->permissions()->exists()) {
            throw new RuntimeException('The Super Admin role has no permissions. Run RolePermissionSeeder before AdminUserSeeder.');
        }

        $existing = User::query()->where('email', $email)->first();

        if ($existing) {
            if ($existing->hasRole('Super Admin')) {
                $this->command?->info('Super Admin already exists. No changes were made.');

                return;
            }

            throw new RuntimeException(
                'An account with ADMIN_SEED_EMAIL already exists without the Super Admin role. '
                .'Refusing to promote an existing customer. Use a different email or resolve the account manually.'
            );
        }

        DB::transaction(function () use ($name, $email, $password, $role) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'email_verified_at' => now(),
            ]);

            $user->assignRole($role);
        });

        $this->command?->info('Super Admin account created. Credentials were not printed.');
    }
}
