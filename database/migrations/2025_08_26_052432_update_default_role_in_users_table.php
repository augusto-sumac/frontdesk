<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Atualizar usuários existentes com role 'manager' para 'supply'
        DB::table('users')->where('role', 'manager')->update(['role' => 'supply']);
        
        // Atualizar usuários existentes com role 'owner' para 'supply'
        DB::table('users')->where('role', 'owner')->update(['role' => 'supply']);
        
        // Atualizar usuários existentes com role 'staff' para 'supply'
        DB::table('users')->where('role', 'staff')->update(['role' => 'supply']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverter usuários com role 'supply' para 'manager' (não é possível distinguir qual era qual)
        DB::table('users')->where('role', 'supply')->update(['role' => 'manager']);
    }
};
