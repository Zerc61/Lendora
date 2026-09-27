<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Profil siswa pada tabel pengguna.
 *
 * Hanya borrower (siswa) yang memakai kolom ini; kolom nullable agar admin,
 * staff, dan teknisi tidak perlu mengisinya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('school_class_id')->nullable()
                ->after('organization_id')
                ->constrained()->nullOnDelete();

            $table->string('photo_path')->nullable()->after('password');
            $table->string('identity_number', 40)->nullable()->after('email'); // NIS / NISN
            $table->date('birth_date')->nullable()->after('identity_number');
            $table->string('gender', 10)->nullable()->after('birth_date');
            $table->string('phone', 25)->nullable()->after('gender');
            $table->string('address', 255)->nullable()->after('phone');
            $table->text('bio')->nullable()->after('address');

            $table->index(['school_class_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['school_class_id']);
            $table->dropIndex(['school_class_id', 'status']);
            $table->dropColumn([
                'school_class_id', 'photo_path', 'identity_number',
                'birth_date', 'gender', 'phone', 'address', 'bio',
            ]);
        });
    }
};
