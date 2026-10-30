<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Fix existing user if present
        $existingUser = DB::table('users')->where('email', 'admin@sogratejerky.test')->first();

        if ($existingUser) {
            $userId = $existingUser->id;
            DB::table('users')->where('id', $userId)->update([
                'password' => bcrypt('password1234'),
            ]);
        } else {
            $userId = DB::table('users')->insertGetId([
                'name' => 'Admin',
                'email' => 'admin@sogratejerky.test',
                'password' => bcrypt('password1234'),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Clear any existing role assignments
        DB::table('model_has_roles')->where('model_id', $userId)->delete();

        $roleId = DB::table('roles')->where('name', 'Super Admin')->pluck('id')->first();

        DB::table('model_has_roles')->insert([
            'role_id' => $roleId,
            'model_type' => User::class,
            'model_id' => $userId,
        ]);

        $this->command->info("Super Admin assigned to user ID {$userId}");
    }
}
