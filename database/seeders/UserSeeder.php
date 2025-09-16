<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'apellido' => 'User',
            'rut' => '12345678-9',
            'email' => 'catita@coffeandcatt.cl',
            'password' => Hash::make('password'),
            'rol' => 0, // 0: admin, 1: bibliotecario
        
        ]);
        User::create([
            'name' => 'Juan',
            'apellido' => 'Pérez',
            'rut' => '12345679-9',
            'email' => 'admin@admin.com',
            'password' => Hash::make('123456'),
            'rol' => 0, // admin
        ]);
    }
}
