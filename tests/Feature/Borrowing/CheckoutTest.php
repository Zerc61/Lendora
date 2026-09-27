<?php

namespace Tests\Feature\Borrowing;

use App\Actions\ApproveReservation;
use App\Actions\CreateReservation;
use App\Enums\AssetStatus;
use App\Enums\BorrowingStatus;
use App\Enums\ReservationStatus;
use App\Models\AssetInspection;
use App\Models\Borrowing;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    private function pendingBorrowing(): Borrowing
    {
        $org = $this->makeOrg();
        $borrower = $this->makeUser('borrower', $org);
        $admin = $this->makeUser('admin', $org);
        $asset = $this->makeAsset($org);

        $reservation = app(CreateReservation::class)->execute(
            $borrower,
            now()->addDay()->setTime(8, 0),
            now()->addDay()->setTime(16, 0),
            'Peminjaman laptop untuk pelatihan guru',
            [$asset->id],
        );

        return app(ApproveReservation::class)->execute($reservation, $admin);
    }

    public function test_checkout_updates_all_statuses_and_records_inspection(): void
    {
        $org = $this->makeOrg();
        $staff = $this->makeUser('staff', $org);
        $borrowing = $this->pendingBorrowing();

        $item = $borrowing->items->first();
        $asset = $item->asset;

        $this->actingAs($staff)
            ->post(route('admin.checkout.store', $borrowing), [
                'items' => [$item->id => ['condition_out' => 'good', 'notes_out' => 'Lengkap dengan charger']],
            ])
            ->assertRedirect(route('admin.borrowings.show', $borrowing));

        $fresh = $borrowing->fresh();

        $this->assertEquals(BorrowingStatus::Borrowed, $fresh->status);
        $this->assertNotNull($fresh->checked_out_at);
        $this->assertEquals($staff->id, $fresh->checked_out_by);
        $this->assertEquals(AssetStatus::Borrowed, $asset->fresh()->status);
        $this->assertEquals(ReservationStatus::Fulfilled, $fresh->reservation->status);
        $this->assertTrue(AssetInspection::where('borrowing_id', $borrowing->id)->where('stage', 'checkout')->exists());
    }

    public function test_cannot_checkout_twice(): void
    {
        $org = $this->makeOrg();
        $staff = $this->makeUser('staff', $org);
        $borrowing = $this->pendingBorrowing();

        $payload = ['items' => [$borrowing->items->first()->id => ['condition_out' => 'good']]];

        $this->actingAs($staff)->post(route('admin.checkout.store', $borrowing), $payload);

        $this->actingAs($staff)
            ->post(route('admin.checkout.store', $borrowing), $payload)
            ->assertSessionHasErrors('status');
    }

    public function test_borrower_cannot_perform_checkout(): void
    {
        $org = $this->makeOrg();
        $borrower = $this->makeUser('borrower', $org);
        $borrowing = $this->pendingBorrowing();

        $this->actingAs($borrower)
            ->post(route('admin.checkout.store', $borrowing), [
                'items' => [$borrowing->items->first()->id => ['condition_out' => 'good']],
            ])
            ->assertForbidden();
    }

    public function test_condition_out_wajib_diisi(): void
    {
        $org = $this->makeOrg();
        $staff = $this->makeUser('staff', $org);
        $borrowing = $this->pendingBorrowing();

        $this->actingAs($staff)
            ->post(route('admin.checkout.store', $borrowing), [
                'items' => [$borrowing->items->first()->id => ['condition_out' => '']],
            ])
            ->assertSessionHasErrors('items.'.$borrowing->items->first()->id.'.condition_out');
    }
}
