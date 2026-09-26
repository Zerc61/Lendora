<?php
// database/migrations/2025_01_01_000013_create_maintenance_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // yang mengerjakan
            $table->string('action', 100);
            $table->text('note')->nullable();
            $table->decimal('cost', 14, 2)->default(0);
            $table->timestamp('performed_at');
            $table->timestamps();
            $table->index('maintenance_ticket_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_logs');
    }
};