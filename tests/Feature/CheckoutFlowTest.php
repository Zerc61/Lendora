<?php

namespace Tests\Feature;

use App\Actions\ApproveReservation;
use App\Enums\AssetStatus;
use App\Enums\BorrowingStatus;
use App\Enums\ReservationStatus;
use App\Models\Asset;
use App\Models\Borrowing;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Alur reservasi → peminjaman → serah-terima.
 *
 * Regression test untuk bug "phantom status": peminjaman pernah dibuat dengan
 * status Pending sementara antrean check-out dan penghitungnya menyaring
 * Approved. Akibatnya transaksi tampil di daftar tapi angka "siap check-out"
 * selalu 0 — bug yang hanya terlihat lewat angka, bukan lewat error.
 */
class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $approver;

    private User $borrower;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->approver = User::where('email', 'admin@lendora.test')->firstOrFail();
        $this->borrower = User::where('email', 'budi@lendora.test')->firstOrFail();
    }

    private function pendingReservation(Asset $asset): Reservation
    {
        $reservation = Reservation::create([
            'organization_id' => $asset->organization_id,
            'user_id' => $this->borrower->id,
            'code' => 'RSV-TEST-001',
            'start_at' => now()->addDay(),
            'end_at' => now()->addDays(3),
            'status' => ReservationStatus::Pending,
            'purpose' => 'Pemeriksaan alur serah-terima',
        ]);

        ReservationItem::create([
            'reservation_id' => $reservation->id,
            'asset_id' => $asset->id,
        ]);

        return $reservation->fresh();
    }

    public function test_persetujuan_reservasi_memasukkan_transaksi_ke_antrean_checkout(): void
    {
        $asset = Asset::where('status', AssetStatus::Available)->firstOrFail();
        $reservation = $this->pendingReservation($asset);

        $before = $this->angkaSiapCheckOut();

        $borrowing = app(ApproveReservation::class)->execute($reservation, $this->approver);

        // Lahir sebagai "disetujui": reservasi sudah disetujui, unit belum diserah-terima.
        $this->assertSame(BorrowingStatus::Approved, $borrowing->status);

        // Memosi state: unit available → reserved.
        $this->assertSame(AssetStatus::Reserved, $asset->fresh()->status);

        // Antrean check-out memuat transaksi…
        $this->actingAs($this->approver)
            ->get(route('admin.checkout.index'))
            ->assertOk()
            ->assertSee($borrowing->code);

        // …dan angka "siap check-out" yang dilihat operator ikut naik.
        $this->assertSame($before + 1, $this->angkaSiapCheckOut());
    }

    /**
     * Angka "Siap Check-out" dibaca dari DOM beranda staff, bukan dari query,
     * supaya yang diuji benar-benar angka yang dilihat operator.
     */
    private function angkaSiapCheckOut(): int
    {
        $staff = User::where('email', 'staff@lendora.test')->firstOrFail();
        $html = $this->actingAs($staff)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertSame(
            1,
            preg_match('/Siap Check-out<\/b>\s*<strong>(\d[\d.,]*)<\/strong>/', $html, $m),
            'Kartu "Siap Check-out" tidak ditemukan di beranda staff — cek markup x-queue-card.'
        );

        return (int) str_replace(['.', ','], '', $m[1]);
    }

    public function test_angka_siap_check_out_cocok_dengan_isi_antrean(): void
    {
        $this->assertSame(
            Borrowing::where('status', BorrowingStatus::Approved->value)->count(),
            $this->angkaSiapCheckOut(),
            'Angka "Siap Check-out" harus sama dengan jumlah transaksi di antrean.'
        );
    }

    public function test_halaman_checkout_menampilkan_transaksi_yang_disetujui(): void
    {
        $asset = Asset::where('status', AssetStatus::Available)->firstOrFail();
        $borrowing = app(ApproveReservation::class)->execute($this->pendingReservation($asset), $this->approver);

        $this->actingAs($this->approver)
            ->get(route('admin.checkout.show', $borrowing))
            ->assertOk()
            ->assertSee($borrowing->code);

        $this->actingAs($this->approver)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('admin.checkout.index'), false);
    }

    public function test_transaksi_yang_sudah_diserah_terima_tidak_lagi_di_antrean(): void
    {
        $asset = Asset::where('status', AssetStatus::Available)->firstOrFail();
        $borrowing = app(ApproveReservation::class)->execute($this->pendingReservation($asset), $this->approver);

        $borrowing->update(['status' => BorrowingStatus::Borrowed]);

        $this->actingAs($this->approver)
            ->get(route('admin.checkout.show', $borrowing))
            ->assertNotFound();

        $this->actingAs($this->approver)
            ->get(route('admin.checkout.index'))
            ->assertOk()
            ->assertDontSee($borrowing->code);
    }

    public function test_daftar_peminjaman_ikut_menawarkan_aksi_check_out(): void
    {
        $asset = Asset::where('status', AssetStatus::Available)->firstOrFail();
        $borrowing = app(ApproveReservation::class)->execute($this->pendingReservation($asset), $this->approver);

        $this->actingAs($this->approver)
            ->get(route('admin.borrowings.index', ['status' => BorrowingStatus::Approved->value]))
            ->assertOk()
            ->assertSee(route('admin.checkout.show', $borrowing), false);
    }
}
