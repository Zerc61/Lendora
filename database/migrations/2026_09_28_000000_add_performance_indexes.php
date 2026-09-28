<?php

// app/database/migrations — index untuk query yang jalan di SETIAP render.
//
// Konteks: DB Aiven di Singapore, ~60ms per round-trip. Setiap query yang
// memindai tabel penuh tidak hanya satu round-trip, tapi 60ms UTUH untuk
// data yang mestinya dibaca dari index. Semua index di bawah dipilih dari
// EXPLAIN pada query yang benar-benar ada di controller/view, bukan tebakan.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // unreadNotifications()->count() → 5-7x per halaman (nav, bell,
        // 2x user-menu). Index notifiable saja tidak menutup read_at, jadi
        // MySQL scan semua notifikasi milik user itu lalu filter di mesin.
        // Verified: sebelum → type=ref key=..._notifiable_... (Using where).
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(
                ['notifiable_type', 'notifiable_id', 'read_at'],
                'notifications_unread_idx'
            );
        });

        // Antrean checkout/checkin + overdue. Dua query per render memakai
        // (status, due_at) — indeks itu sudah ada. checked_out_at dan
        // returned_at sama sekali tidak punya, padahal keduanya dipakai
        // staff dashboard (trend + count hari ini).
        Schema::table('borrowings', function (Blueprint $table) {
            $table->index('checked_out_at', 'borrowings_checked_out_at_idx');
            $table->index('returned_at', 'borrowings_returned_at_idx');
            $table->index(['status', 'created_at'], 'borrowings_status_created_idx');
        });

        // Antrean reservasi pending + ->oldest() (dashboard staff).
        Schema::table('reservations', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'reservations_status_created_idx');
        });

        // Workbar teknisi: (technician_id, status). Dipakai technician()
        // di DashboardController::technician() dan Navigation::ticketStatusCounts.
        Schema::table('maintenance_tickets', function (Blueprint $table) {
            $table->index(
                ['technician_id', 'status'],
                'maintenance_tickets_technician_status_idx'
            );
        });

        // IssueController mem-pluck issue_id dari tiket yang punya issue —
        // kolom ini tidak punya index, jadi full scan.
        Schema::table('maintenance_tickets', function (Blueprint $table) {
            $table->index('issue_id', 'maintenance_tickets_issue_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_tickets', function (Blueprint $table) {
            $table->dropIndex('maintenance_tickets_issue_id_idx');
            $table->dropIndex('maintenance_tickets_technician_status_idx');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex('reservations_status_created_idx');
        });

        Schema::table('borrowings', function (Blueprint $table) {
            $table->dropIndex('borrowings_status_created_idx');
            $table->dropIndex('borrowings_returned_at_idx');
            $table->dropIndex('borrowings_checked_out_at_idx');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_unread_idx');
        });
    }
};
