<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change enum to include USD
        DB::statement("ALTER TABLE `payments` MODIFY COLUMN `currency` ENUM('KM', 'EUR', 'USD') NOT NULL DEFAULT 'KM'");
    }

    public function down(): void
    {
        // Revert back to original enum (only if no USD values exist)
        DB::statement("ALTER TABLE `payments` MODIFY COLUMN `currency` ENUM('KM', 'EUR') NOT NULL DEFAULT 'KM'");
    }
};
