<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Manager
        DB::table('users')->insert([
            'id'       => 1,
            'name'     => 'Fazri Suhada',
            'email'    => 'fazri@mail.com',
            'password' => Hash::make('rahasia'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Staff
        DB::table('users')->insert([
            'id'       => 2,
            'name'     => 'Jhon Doe',
            'email'    => 'jhon@mail.com',
            'password' => Hash::make('rahasia'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

    }
}