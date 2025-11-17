<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Crear usuario administrador por defecto
        User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'admin',
                'password' => Hash::make('admin123'),
                'role' => 'admin'
            ]
        );
//aa
        echo "✅ Usuario admin creado correctamente\n";
        echo "Username: admin\n";
        echo "Password: admin123\n";
    }
}
