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
        // Check if admin user already exists
        $existingAdmin = User::where('email', 'joao.andrade@unifil.br')->first();
        
        if (!$existingAdmin) {
            User::create([
                'name' => 'João Andrade',
                'email' => 'joao.andrade@unifil.br',
                'matricula' => '000000001', // Special admin matricula
                'curso' => 'Administração do Sistema',
                'password' => Hash::make('Admin@2025!UniFil'), // Secure password
                'role' => 'admin',
                'email_verified_at' => now(),
            ]);
            
            $this->command->info('Admin user created successfully!');
            $this->command->info('Email: joao.andrade@unifil.br');
            $this->command->info('Password: Admin@2025!UniFil');
            $this->command->warn('IMPORTANT: Please share this password securely with the administrator and consider changing it after first login.');
        } else {
            $this->command->info('Admin user already exists.');
        }
    }
}