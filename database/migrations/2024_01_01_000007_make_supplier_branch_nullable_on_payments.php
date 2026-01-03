<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Drop existing foreign keys
            $table->dropForeign(['supplier_id']);
            $table->dropForeign(['branch_id']);
        });

        Schema::table('payments', function (Blueprint $table) {
            // Make columns nullable
            $table->unsignedBigInteger('supplier_id')->nullable()->change();
            $table->unsignedBigInteger('branch_id')->nullable()->change();
            
            // Re-add foreign keys with nullable support
            $table->foreign('supplier_id')->references('id')->on('suppliers')->nullOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropForeign(['branch_id']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('supplier_id')->nullable(false)->change();
            $table->unsignedBigInteger('branch_id')->nullable(false)->change();
            
            $table->foreign('supplier_id')->references('id')->on('suppliers')->restrictOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->restrictOnDelete();
        });
    }
};
