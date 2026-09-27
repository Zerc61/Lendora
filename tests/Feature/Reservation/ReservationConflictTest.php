<?php

namespace Tests\Feature\Reservation;

use App\Actions\CreateReservation;
use App\Models\Reservation;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Kriteria MVP inti: sistem menolak konflik reservasi asset/waktu sama.
 *
 * Konflik dicek di dalam DB::transaction + lockForUpdate, jadi dua request
 * bersamaan pada aset yang sama diserialisasi — tidak ada double booking.
 */
class ReservationConflictTest extends TestCase
{
    private function slot(int $startHour, int $endHour): array
    {
        $day = now()->addDay()->startOfDay();

        return [$day->copy()->setTime($startHour, 0), $day->copy()->setTime($endHour, 0)];
    }

    public function test_rejects_overlapping_reservation_on_same_asset(): void
    {
        $org = $this->makeOrg();
        $borrower = $this->makeUser('borrower', $org);
        $asset = $this->makeAsset($org);

        [$s1, $e1] = $this->slot(8, 10);
        app(CreateReservation::class)->execute($borrower, $s1, $e1, 'Praktikum kelas satu', [$asset->id]);

        [$s2, $e2] = $this->slot(9, 11); // overlap dengan 08–10

        $this->expectException(ValidationException::class);
        app(CreateReservation::class)->execute($borrower, $s2, $e2, 'Keperluan acara sekolah', [$asset->id]);
    }

    public function test_allows_back_to_back_reservations(): void
    {
        $org = $this->makeOrg();
        $borrower = $this->makeUser('borrower', $org);
        $asset = $this->makeAsset($org);

        [$s1, $e1] = $this->slot(8, 10);
        [$s2, $e2] = $this->slot(10, 12); // bersentuhan, bukan overlap

        app(CreateReservation::class)->execute($borrower, $s1, $e1, 'Praktikum sesi pagi', [$asset->id]);
        app(CreateReservation::class)->execute($borrower, $s2, $e2, 'Praktikum sesi dua', [$asset->id]);

        $this->assertCount(2, Reservation::all());
    }

    public function test_rejects_asset_that_is_borrowed(): void
    {
        $org = $this->makeOrg();
        $borrower = $this->makeUser('borrower', $org);
        $asset = $this->makeAsset($org, 'borrowed');

        [$s, $e] = $this->slot(8, 10);

        $this->expectException(ValidationException::class);
        app(CreateReservation::class)->execute($borrower, $s, $e, 'Perjalanan dinas luar', [$asset->id]);
    }

    public function test_rejects_asset_under_maintenance(): void
    {
        $org = $this->makeOrg();
        $borrower = $this->makeUser('borrower', $org);
        $asset = $this->makeAsset($org, 'maintenance');

        [$s, $e] = $this->slot(8, 10);

        $this->expectException(ValidationException::class);
        app(CreateReservation::class)->execute($borrower, $s, $e, 'Uji fungsi perangkat', [$asset->id]);
    }

    public function test_different_assets_can_share_same_time(): void
    {
        $org = $this->makeOrg();
        $borrower = $this->makeUser('borrower', $org);
        $a = $this->makeAsset($org);
        $b = $this->makeAsset($org);

        [$s, $e] = $this->slot(8, 10);

        app(CreateReservation::class)->execute($borrower, $s, $e, 'Ekskul robotik umum', [$a->id]);
        app(CreateReservation::class)->execute($borrower, $s, $e, 'Ekskul desain grafis', [$b->id]);

        $this->assertCount(2, Reservation::all());
    }

    public function test_http_submission_creates_pending_reservation(): void
    {
        $org = $this->makeOrg();
        $borrower = $this->makeUser('borrower', $org);
        $asset = $this->makeAsset($org);

        [$s, $e] = $this->slot(8, 10);

        $this->actingAs($borrower)
            ->post(route('my.reservations.store'), [
                'start_at' => $s->format('Y-m-d\TH:i'),
                'end_at' => $e->format('Y-m-d\TH:i'),
                'purpose' => 'Mengajar multimedia di kelas',
                'asset_ids' => [$asset->id],
            ])
            ->assertRedirect(route('my.reservations.index'));

        $this->assertDatabaseHas('reservations', [
            'user_id' => $borrower->id,
            'status' => 'pending',
        ]);
    }

    public function test_reservasi_tanpa_aset_ditolak(): void
    {
        $org = $this->makeOrg();
        $borrower = $this->makeUser('borrower', $org);

        [$s, $e] = $this->slot(8, 10);

        $this->actingAs($borrower)
            ->post(route('my.reservations.store'), [
                'start_at' => $s->format('Y-m-d\TH:i'),
                'end_at' => $e->format('Y-m-d\TH:i'),
                'purpose' => 'Peminjaman tanpa memilih unit aset',
                'asset_ids' => [],
            ])
            ->assertSessionHasErrors('asset_ids');
    }
}
