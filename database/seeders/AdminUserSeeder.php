<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['mobile_number' => '1234567890'],
            [
                'business_code' => 'DECORA001',
                'business_name' => 'Decora Business',
                'role'          => 'admin',
                'name'          => 'Super Admin',
                'password'      => Hash::make('admin@123'),
                'gst_number'    => null,
                'address'       => 'Head Office Address',
                'is_active'     => 1,
                 'email'         => 'admin@decora.com',
            ]
        );
    }
}
