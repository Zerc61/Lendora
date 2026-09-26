<?php
// database/migrations/2025_01_01_000011_create_issues_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();               // ISS-XXXX
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('borrowing_id')->nullable()->constrained()->nullOnDelete(); // issue saat pengembalian
            $table->foreignId('reported_by')->constrained('users')->restrictOnDelete();
            $table->string('type', 30)->index();                // damage | loss | other
            $table->string('severity', 20)->default('medium')->index();
            $table->string('status', 30)->default('open')->index(); // open|investigating|resolved|rejected|closed
            $table->text('description');
            $table->text('resolution')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};