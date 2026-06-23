<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Users table already created with nullable name/password in L13 baseline.
        // Kept for compatibility with historical migration order on upgraded DBs.
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                // no-op when columns already nullable
            });
        }
    }

    public function down(): void
    {
        //
    }
};
