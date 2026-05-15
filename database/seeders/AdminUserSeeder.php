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
        // Manager
        $manager = User::firstOrCreate(
            ['email' => 'manager@e-gudang.com'],
            [
                'name' => 'Manager',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $manager->syncRoles(['Manager']);

        // Kepala Gudang
        $kepalaGudang = User::firstOrCreate(
            ['email' => 'kepalagudang@e-gudang.com'],
            [
                'name' => 'Kepala Gudang',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $kepalaGudang->syncRoles(['Kepala Gudang']);

        // Operator Gudang
        $operatorGudang = User::firstOrCreate(
            ['email' => 'operator@e-gudang.com'],
            [
                'name' => 'Operator Gudang',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $operatorGudang->syncRoles(['Operator Gudang']);
    }
}
