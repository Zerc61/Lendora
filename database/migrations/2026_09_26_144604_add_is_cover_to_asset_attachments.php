<?php
// database/migrations/2026_09_26_144604_add_is_cover_to_asset_attachments.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_attachments', function (Blueprint $table) {
            $table->boolean('is_cover')->default(false)->after('type'); // foto/video utama halaman produk
        });
    }

    public function down(): void
    {
        Schema::table('asset_attachments', function (Blueprint $table) {
            $table->dropColumn('is_cover');
        });
    }
};
