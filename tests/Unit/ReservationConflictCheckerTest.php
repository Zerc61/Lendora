<?php

namespace Tests\Unit;

use App\Models\Reservation;
use App\Services\ReservationConflictChecker;
use Tests\TestCase;

/**
 * Konflik reservasi diuji terisolasi dari HTTP/controller.
 *
 * Catatan penting: checker hanya melihat status pending/approved — reservasi
 * yang sudah ditolak/dibatalkan tidak boleh memblokir unit.
 */
class ReservationConflictCheckerTest extends TestCase
{
    private ReservationConflictChecker $checker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->checker = new ReservationConflictChecker;
    }

    private function reserveItem($asset, string $status, $start, $end): Reservation
    {
        $user = $this->makeUser('borrower', $asset->organization);

        $reservation = Reservation::create([
            'code' => 'RSV-T'.substr(md5(uniqid('', true)), 0, 8),
            'organization_id' => $asset->organization_id,
            'user_id' => $user->id,
            'start_at' => $start,
            'end_at' => $end,
            'status' => $status,
        ]);
        $reservation->items()->create(['asset_id' => $asset->id, 'quantity' => 1]);

        return $reservation;
    }

    public function test_detects_pending_overlap(): void
    {
        $org = $this->makeOrg();
        $asset = $this->makeAsset($org);

        $this->reserveItem($asset, 'pending', now()->addDay()->setTime(8, 0), now()->addDay()->setTime(10, 0));

        $this->assertTrue($this->checker->conflictForAsset(
            $asset,
            now()->addDay()->setTime(9, 0),
            now()->addDay()->setTime(11, 0),
        ));
    }

    public function test_detects_approved_overlap(): void
    {
        $org = $this->makeOrg();
        $asset = $this->makeAsset($org);

        $this->reserveItem($asset, 'approved', now()->addDay()->setTime(8, 0), now()->addDay()->setTime(10, 0));

        $this->assertTrue($this->checker->conflictForAsset(
            $asset,
            now()->addDay()->setTime(9, 0),
            now()->addDay()->setTime(11, 0),
        ));
    }

    public function test_no_conflict_outside_range(): void
    {
        $org = $this->makeOrg();
        $asset = $this->makeAsset($org);

        $this->reserveItem($asset, 'pending', now()->addDay()->setTime(8, 0), now()->addDay()->setTime(10, 0));

        $this->assertFalse($this->checker->conflictForAsset(
            $asset,
            now()->addDay()->setTime(12, 0),
            now()->addDay()->setTime(14, 0),
        ));
    }

    public function test_ignores_cancelled_reservations(): void
    {
        $org = $this->makeOrg();
        $asset = $this->makeAsset($org);

        $this->reserveItem($asset, 'cancelled', now()->addDay()->setTime(8, 0), now()->addDay()->setTime(10, 0));

        $this->assertFalse($this->checker->conflictForAsset(
            $asset,
            now()->addDay()->setTime(9, 0),
            now()->addDay()->setTime(11, 0),
        ));
    }

    public function test_can_ignore_specific_reservation(): void
    {
        $org = $this->makeOrg();
        $asset = $this->makeAsset($org);

        $own = $this->reserveItem($asset, 'pending', now()->addDay()->setTime(8, 0), now()->addDay()->setTime(10, 0));

        $this->assertFalse($this->checker->conflictForAsset(
            $asset,
            now()->addDay()->setTime(9, 0),
            now()->addDay()->setTime(11, 0),
            $own->id,
        ));
    }
}
