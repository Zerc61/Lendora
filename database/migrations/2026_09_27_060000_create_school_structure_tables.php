<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Struktur sekolah pada sebuah organisasi.
 *
 *   Organization → Program (jurusan/peminatan) → SchoolClass (kelas)
 *   Organization → Classroom (ruang kelas)
 *
 * Programbring education_level sehingga satu jenjang bisa punya beberapa
 jurusan/peminatan (SMA → IPA & IPS, SMK → RPL, TKJ, TKR, …).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('education_level', 10)->index(); // sd|smp|sma|smk|ma
            $table->string('name', 100);                    // IPA, IPS, RPL, TKJ, …
            $table->string('code', 30);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'education_level']);
        });

        Schema::create('school_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);          // "X IPA 1", "VIII B"
            $table->string('school_year', 15);   // 2026/2027
            $table->unsignedTinyInteger('capacity')->default(30);
            $table->foreignId('homeroom_teacher_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['program_id', 'name', 'school_year']);
        });

        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);        // "R. 1-A", "Lab IPA 1"
            $table->unsignedSmallInteger('capacity')->default(36);
            $table->string('notes', 200)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classrooms');
        Schema::dropIfExists('school_classes');
        Schema::dropIfExists('programs');
    }
};
