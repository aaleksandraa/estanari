<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_descriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->text('description');
            $table->timestamps();

            // Unique constraint - one saved description per supplier-branch combination
            $table->unique(['supplier_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_descriptions');
    }
};
