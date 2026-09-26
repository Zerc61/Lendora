<?php
// database/migrations/2025_01_01_000010_create_asset_inspections_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->restrictOnDelete();
            $table->foreignId('borrowing_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('inspector_id')->constrained('users')->restrictOnDelete();
            $table->string('stage', 20)->index();               // checkout | checkin
            $table->string('condition', 30);                    // kondisi hasil inspeksi
            $table->json('checklist')->nullable();              // checklist kondisi (PDF H/I)
            $table->text('notes')->nullable();
            $table->timestamp('inspected_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_inspections');
    }
};