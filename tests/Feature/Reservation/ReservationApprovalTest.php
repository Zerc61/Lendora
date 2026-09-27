<?php

namespace Tests\Feature\Reservation;

use App\Actions\ApproveReservation;
use App\Actions\CreateReservation;
use App\Actions\RejectReservation;
use App\Enums\AssetStatus;
use App\Enums\BorrowingStatus;
use App\Enums\ReservationStatus;
use App\Notifications\ReservationStatusNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ReservationApprovalTest extends TestCase
{
    private function createPending(): array
    {
        $org = $this->makeOrg();
        $borrower = $this->makeUser('borrower', $org);
        $admin = $this->makeUser('admin', $org);
        $asset = $this->makeAsset($org);

        $reservation = app(CreateReservation::class)->execute(
            $borrower,
            now()->addDay()->setTime(8, 0),
            now()->addDay()->setTime(10, 0),
            'Peminjaman untuk praktikum kelas',
            [$asset->id],
        );

        return [$reservation, $borrower, $admin, $asset];
    }

    public function test_approval_creates_borrowing_and_reserves_asset(): void
    {
        [$reservation, $borrower, $admin, $asset] = $this->createPending();

        $borrowing = app(ApproveReservation::class)->execute($reservation, $admin);

        $this->assertEquals(ReservationStatus::Approved, $reservation->fresh()->status);
        // Peminjaman lahir "disetujui" (bukan pending) supaya langsung tampil
        // di antrean check-out dan ikut terhitung — lihat CheckoutFlowTest.
        $this->assertEquals(BorrowingStatus::Approved, $borrowing->status);
        $this->assertEquals($borrower->id, $borrowing->borrower_id);
        $this->assertEquals($asset->id, $borrowing->items->first()->asset_id);
        $this->assertEquals(AssetStatus::Reserved, $asset->fresh()->status);
    }

    public function test_only_pending_can_be_approved(): void
    {
        [$reservation, , $admin] = $this->createPending();

        app(ApproveReservation::class)->execute($reservation, $admin);

        $this->expectException(ValidationException::class);
        app(ApproveReservation::class)->execute($reservation, $admin); // kedua kali ditolak
    }

    public function test_approval_notifies_borrower(): void
    {
        [$reservation, $borrower, $admin] = $this->createPending();

        Notification::fake();

        app(ApproveReservation::class)->execute($reservation, $admin);

        Notification::assertSentTo($borrower, ReservationStatusNotification::class);
    }

    public function test_borrower_cannot_approve_via_http(): void
    {
        [$reservation, $borrower] = $this->createPending();

        $this->actingAs($borrower)
            ->post(route('admin.reservations.approve', $reservation))
            ->assertForbidden();
    }

    public function test_rejection_records_reason_and_notifies(): void
    {
        [$reservation, $borrower, $admin] = $this->createPending();

        Notification::fake();

        app(RejectReservation::class)->execute($reservation, $admin, 'Unit dibutuhkan untuk ujian');

        $fresh = $reservation->fresh();

        $this->assertEquals(ReservationStatus::Rejected, $fresh->status);
        $this->assertSame('Unit dibutuhkan untuk ujian', $fresh->rejection_reason);
        $this->assertEquals($admin->id, $fresh->approved_by);

        Notification::assertSentTo($borrower, ReservationStatusNotification::class);
    }

    public function test_rejection_melepas_reservasi_unit(): void
    {
        // Unit yang ditolak harus bisa dipinjam lagi (statusnya tidak menggantung).
        [$reservation, , $admin, $asset] = $this->createPending();

        app(RejectReservation::class)->execute($reservation, $admin, 'Jadwal bentrok dengan inventaris');

        $this->assertEquals(AssetStatus::Available, $asset->fresh()->status);
    }
}
