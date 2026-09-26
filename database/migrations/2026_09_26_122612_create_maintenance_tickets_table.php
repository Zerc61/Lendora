<?php
// database/migrations/2025_01_01_000012_create_maintenance_tickets_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();               // MTC-XXXX
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->restrictOnDelete();
            $table->foreignId('issue_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reported_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 30)->default('corrective')->index(); // preventive|corrective|inspection
            $table->string('priority', 20)->default('medium')->index();
            $table->string('status', 30)->default('open')->index(); // open|assigned|in_progress|waiting_parts|completed|verified|cancelled
            $table->text('description');
            $table->text('diagnosis')->nullable();
            $table->decimal('cost', 14, 2)->default(0);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'status']);
            $table->index(['asset_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_tickets');
    }
};