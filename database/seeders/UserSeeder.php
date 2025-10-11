<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Super Admin
        $superadmin = User::updateOrCreate(
            ['phone' => '0876543210'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Super Admin',
                'gender' => 'male',
                'address' => 'Jl. Jalan Raya',
                'birthdate' => '2000-01-01',
                'password' => Hash::make('Password123!'),
                'role' => 'super_admin',
                'avatar' => null,
            ]
        );

        $superadmin->assignRole('super_admin');

        // Admin
        $admin = User::updateOrCreate(
            ['phone' => '0876543211'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Admin',
                'gender' => 'female',
                'address' => 'Jl. Jalan Glempang',
                'birthdate' => '2002-01-01',
                'password' => Hash::make('Password123!'),
                'role' => 'admin',
                'avatar' => null,
            ]
        );

        $admin->assignRole('admin');

        // Member
        $member = User::updateOrCreate(
            ['phone' => '0876543212'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Member',
                'gender' => 'female',
                'address' => 'Jl. Jalan Pekuncen',
                'birthdate' => '2003-01-01',
                'password' => Hash::make('Password123!'),
                'role' => 'member',
                'avatar' => null,
            ]
        );

        $member->assignRole('member');
    }
}
