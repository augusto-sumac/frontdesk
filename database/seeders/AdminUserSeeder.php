<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Verificar se já existe um usuário admin
        if (User::where('email', 'admin@frontdesk.com')->exists()) {
            $this->command->info('Usuário administrador já existe!');
            return;
        }

        // Criar usuário administrador padrão
        User::create([
            'name' => 'Administrador',
            'last_name' => 'Sistema',
            'email' => 'admin@frontdesk.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'phone' => '(11) 99999-9999',
            'company_name' => 'FrontDesk Pro',
            'is_active' => true,
        ]);

        $this->command->info('Usuário administrador criado com sucesso!');
        $this->command->info('Email: admin@frontdesk.com');
        $this->command->info('Senha: admin123');
        $this->command->warn('IMPORTANTE: Altere a senha após o primeiro login!');
    }
}
