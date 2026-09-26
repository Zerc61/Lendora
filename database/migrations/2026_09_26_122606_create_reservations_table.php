<?php
// database/migrations/2025_01_01_000006_create_reservations_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();               // RSV-XXXX
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete(); // pemesan
            $table->timestamp('start_at');
            $table->timestamp('end_at');
            $table->string('status', 30)->default('pending')->index(); // pending|approved|rejected|cancelled|expired|fulfilled
            $table->text('purpose')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'status']);
            $table->index(['start_at', 'end_at']); // untuk conflict detection
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};