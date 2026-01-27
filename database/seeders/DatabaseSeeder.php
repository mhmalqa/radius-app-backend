<?php

namespace Database\Seeders;



    use App\Models\AppUser;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

AppUser::create([
    'username'    => 'admin',
    'password'    => 'admin_mhmalqa',   // سيتم تشفيره تلقائياً (cast: hashed)
    'firstname'   => 'Admin',
    'phone'       => '0999999999',
    'email'       => 'admin@example.com',
    'role'        => 2,              // 2 = Admin
    'is_active'   => true,
    'live_access' => true,
    'language'    => 'ar',
]);
    }
}
