<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class userSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Misalnya, jika Anda ingin menambahkan pengguna baru
        $newUser = User::updateOrCreate(
            [
                'email' => 'user@gmail.com', // Pastikan email unik
            ],
            [
                'name' => 'User',
                'password' => bcrypt('12345678'), // Ganti password sesuai kebutuhan
                'roles_id' => 2, // ID peran yang sesuai
            ]
        );

        // Memberikan role kepada pengguna baru berdasarkan roles_id
        if ($newUser['roles_id'] == 1) {
            $newUser->assignRole('admin'); // Assign role admin
        } elseif ($newUser['roles_id'] == 2) {
            $newUser->assignRole('owner'); // Assign role owner
        }
    }
}
