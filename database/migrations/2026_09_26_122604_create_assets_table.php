<?php
// database/migrations/2025_01_01_000004_create_assets_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('asset_code', 50)->unique();             // unique constraint (PDF bag. 6)
            $table->string('serial_number', 100)->nullable()->unique();
            $table->string('status', 30)->default('available')->index(); // state machine asset
            $table->string('condition', 30)->default('good')->index();   // kondisi fisik
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 14, 2)->nullable();   // harga (PDF bag. 4D)
            $table->date('warranty_until')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['organization_id', 'status']);
            $table->index(['status', 'condition']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};