<?php
// database/migrations/2025_01_01_000009_create_borrowing_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrowing_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('borrowing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('condition_out', 30)->nullable();  // kondisi saat check-out
            $table->string('condition_in', 30)->nullable();   // kondisi saat check-in
            $table->text('notes_out')->nullable();
            $table->text('notes_in')->nullable();
            $table->timestamps();
            $table->index('asset_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrowing_items');
    }
};