<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User; // ✅ INI YANG PENTING
use Illuminate\Support\Facades\Hash;

class OperatorSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'operator@polije.ac.id'],
            [
                'name' => 'Operator PolijeCare',
                'password' => Hash::make('Operator@123'),
                'role' => 'operator',
            ]
        );
    }
}
