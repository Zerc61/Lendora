<?php
// database/migrations/2025_01_01_000007_create_reservation_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained()->restrictOnDelete();      // reservasi unit spesifik
            $table->foreignId('asset_type_id')->nullable()->constrained()->restrictOnDelete(); // reservasi per tipe + quantity
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();
            $table->index('asset_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_items');
    }
};