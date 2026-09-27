<?php

namespace Tests\Feature;

use App\Enums\IssueSeverity;
use App\Enums\MaintenancePriority;
use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\Issue;
use App\Models\MaintenanceTicket;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Filter yang tampil di UI harus benar-benar menyaring.
 *
 * Regression test untuk filter yang sudah didukung controller tapi tidak
 * pernah dirender di view (severity, priority) serta rentang tanggal audit
 * log — tanpa test, filter yang tidak berfungsi tidak akan pernah terlihat.
 */
class FilterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->admin = User::where('email', 'admin@lendora.test')->firstOrFail();
    }

    public function test_filter_severity_issue_menyaring(): void
    {
        $asset = Asset::firstOrFail();
        $reporter = User::where('email', 'budi@lendora.test')->firstOrFail();

        Issue::create([
            'organization_id' => $asset->organization_id,
            'asset_id' => $asset->id,
            'reported_by' => $reporter->id,
            'code' => 'ISS-TEST-CRIT',
            'type' => \App\Enums\IssueType::Damage,
            'severity' => IssueSeverity::Critical,
            'status' => \App\Enums\IssueStatus::Open,
            'description' => 'Unit tidak menyala.',
        ]);
        Issue::create([
            'organization_id' => $asset->organization_id,
            'asset_id' => $asset->id,
            'reported_by' => $reporter->id,
            'code' => 'ISS-TEST-LOW',
            'type' => \App\Enums\IssueType::Damage,
            'severity' => IssueSeverity::Low,
            'status' => \App\Enums\IssueStatus::Open,
            'description' => 'Goresan ringan pada casing.',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.issues.index', ['severity' => IssueSeverity::Critical->value]))
            ->assertOk()
            ->assertSee('f-severity', false);

        $response->assertSee('ISS-TEST-CRIT', false);
        $response->assertDontSee('ISS-TEST-LOW', false);

        $filtered = Issue::where('severity', IssueSeverity::Critical->value)->pluck('code');

        $this->assertContains('ISS-TEST-CRIT', $filtered);
        $this->assertNotContains('ISS-TEST-LOW', $filtered);
    }

    public function test_filter_priority_tiket_menyaring(): void
    {
        $asset = Asset::firstOrFail();
        $reporter = User::where('email', 'staff@lendora.test')->firstOrFail();

        MaintenanceTicket::create([
            'organization_id' => $asset->organization_id,
            'asset_id' => $asset->id,
            'reported_by' => $reporter->id,
            'code' => 'TKT-TEST-CRIT',
            'type' => \App\Enums\MaintenanceType::Corrective,
            'priority' => MaintenancePriority::Critical,
            'status' => \App\Enums\MaintenanceStatus::Open,
            'description' => 'Power supply mati.',
        ]);
        MaintenanceTicket::create([
            'organization_id' => $asset->organization_id,
            'asset_id' => $asset->id,
            'reported_by' => $reporter->id,
            'code' => 'TKT-TEST-LOW',
            'type' => \App\Enums\MaintenanceType::Preventive,
            'priority' => MaintenancePriority::Low,
            'status' => \App\Enums\MaintenanceStatus::Open,
            'description' => 'Pembersihan berkala.',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.tickets.index', ['priority' => MaintenancePriority::Critical->value]))
            ->assertOk()
            ->assertSee('f-priority', false);

        $response->assertSee('TKT-TEST-CRIT', false);
        $response->assertDontSee('TKT-TEST-LOW', false);

        // Periksa hasil filter yang sama dengan controller, bukan jumlah absolut
        // (jumlah dataset berubah setiap kali seeder ditambah).
        $filtered = MaintenanceTicket::where('priority', MaintenancePriority::Critical->value)->pluck('code');

        $this->assertContains('TKT-TEST-CRIT', $filtered);
        $this->assertNotContains('TKT-TEST-LOW', $filtered);
    }

    public function test_filter_rentang_tanggal_audit_log_menyaring(): void
    {
        $asset = Asset::firstOrFail();

        // `created_at` tidak ada di $fillable (model read-only), jadi harus
        // di-forceFill agar catatan uji benar-benar berada di luar rentang.
        AuditLog::create([
            'actor_id' => $this->admin->id,
            'action' => 'created',
            'subject_type' => $asset::class,
            'subject_id' => $asset->id,
        ])->forceFill(['created_at' => now()->subDays(10)])->save();

        AuditLog::create([
            'actor_id' => $this->admin->id,
            'action' => 'updated',
            'subject_type' => $asset::class,
            'subject_id' => $asset->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.audit-logs.index', [
                'from' => now()->subDay()->toDateString(),
                'to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('f-from', false);

        // Baris 10 hari lalu harus tersaring keluar; baris hari ini tetap tampil.
        $response->assertDontSee(
            now()->subDays(10)->format('d M Y'),
            false,
            'Catatan di luar rentang tanggal tidak boleh ikut tampil.'
        );

        // Hitung langsung tidak bisa dipatok ke angka mutlak karena seeder
        // juga menulis catatan untuk aset yang sama; yang penting rentang
        // mengurangi hasil dan tidak mengosongkannya.
        $semua = AuditLog::where('subject_type', $asset::class)
            ->where('subject_id', $asset->id)
            ->count();
        $dalamRentang = AuditLog::where('subject_type', $asset::class)
            ->where('subject_id', $asset->id)
            ->where('created_at', '>=', now()->subDay()->startOfDay())
            ->where('created_at', '<=', now()->endOfDay())
            ->count();

        $this->assertGreaterThan(0, $dalamRentang, 'Rentang hari ini harus masih berisi catatan.');
        $this->assertLessThan($semua, $dalamRentang, 'Rentang tanggal harus mengurangi jumlah catatan.');
    }
}
