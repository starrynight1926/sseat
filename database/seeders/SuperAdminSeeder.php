<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SUPER_ADMIN_EMAIL', 'admin@sseat.local');
        $password = env('SUPER_ADMIN_PASSWORD', 'admin123456');

        $user = User::firstOrNew(['email' => $email]);
        $user->name = $user->name ?: 'Super Admin';
        $user->role = User::ROLE_SUPER_ADMIN;
        $user->is_suspended = false;
        if (!$user->exists) {
            $user->password = Hash::make($password);
        }
        $user->save();

        $this->command?->info("Super admin: {$email}" . ($user->wasRecentlyCreated ? " (password: {$password})" : ' (existing)'));
    }
}
