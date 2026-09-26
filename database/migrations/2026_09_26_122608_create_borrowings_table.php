<?php
// database/migrations/2025_01_01_000008_create_borrowings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrowings', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();               // BRW-XXXX
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('borrower_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('pending')->index(); // pending|approved|borrowed|returned|rejected|cancelled|overdue
            $table->text('purpose')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamp('due_at')->nullable()->index();
            $table->timestamp('returned_at')->nullable();
            $table->foreignId('checked_out_by')->nullable()->constrained('users')->nullOnDelete(); // operator serah-terima (PDF H)
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();  // operator penerimaan (PDF I)
            $table->text('checkin_notes')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'status']);
            $table->index(['status', 'due_at']); // untuk deteksi overdue
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrowings');
    }
};