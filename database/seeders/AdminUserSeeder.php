<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get admin credentials from environment variables
        $adminEmail = env('ADMIN_EMAIL', 'admin@facepoint.com');
        $adminPassword = env('ADMIN_PASSWORD', 'admin123');
        $adminName = env('ADMIN_NAME', 'Administrator');
        $adminMatricula = env('ADMIN_MATRICULA', '000000000');

        // Check if admin user already exists
        $existingAdmin = User::where('email', $adminEmail)->first();

        if (!$existingAdmin) {
            User::create([
                'name' => $adminName,
                'email' => $adminEmail,
                'matricula' => $adminMatricula,
                'curso' => 'Administração do Sistema',
                'password' => Hash::make($adminPassword),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]);

            $this->command->info('Admin user created successfully!');
            $this->command->info('Email: ' . $adminEmail);
            $this->command->info('Password: ' . $adminPassword);
            $this->command->warn('IMPORTANT: These credentials are stored in your .env file. Share securely and change after first login.');
        } else {
            $this->command->info('Admin user already exists.');
        }
    }
}