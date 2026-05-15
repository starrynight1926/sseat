<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Existing 'user' rows → 'builder'. Old 'staff' → 'builder'.
        \Illuminate\Support\Facades\DB::table('users')
            ->whereIn('role', ['user', 'staff'])
            ->update(['role' => 'builder']);
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\DB::table('users')
            ->whereIn('role', ['staff', 'customer'])
            ->update(['role' => 'user']);
    }
};
