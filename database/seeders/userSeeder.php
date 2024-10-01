<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class userSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // User::updateOrCreate([
        //     'name' => 'Gilang',
        //     'roles_id' => 0,
        //     'email' => 'Gilang@gmail.com',
        //     'password' => '12345678',
        // ]);
        // User::updateOrCreate([
        //     'name' => 'owner',
        //     'roles_id' => 0,
        //     'email' => 'owner@gmail.com',
        //     'password' => '12345678',
        // ]);
        User::updateOrCreate([
            'name' => 'Alfin',
            'roles_id' => 0,
            'email' => 'alfin@gmail.com',
            'password' => '12345678',
        ]);
    }
}
