<?php

namespace Tests\Feature\Borrowing;

use App\Actions\ApproveReservation;
use App\Actions\CheckinBorrowing;
use App\Actions\CheckoutBorrowing;
use App\Actions\CreateReservation;
use App\Enums\AssetStatus;
use App\Enums\BorrowingStatus;
use App\Models\AssetInspection;
use App\Models\Borrowing;
use App\Models\Issue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CheckinTest extends TestCase
{
    private function activeBorrowing(): array
    {
        $org = $this->makeOrg();
        $borrower = $this->makeUser('borrower', $org);
        $admin = $this->makeUser('admin', $org);
        $staff = $this->makeUser('staff', $org);
        $asset = $this->makeAsset($org);

        $reservation = app(CreateReservation::class)->execute(
            $borrower,
            now()->addDay()->setTime(8, 0),
            now()->addDays(2)->setTime(16, 0),
            'Peminjaman kamera untuk dokumentasi acara',
            [$asset->id],
        );

        $borrowing = app(ApproveReservation::class)->execute($reservation, $admin);

        app(CheckoutBorrowing::class)->execute(
            $borrowing,
            $staff,
            [$borrowing->items->first()->id => ['condition_out' => 'good']],
        );

        return [$borrowing->fresh(), $staff];
    }

    private function pendingBorrowing(): Borrowing
    {
        $org = $this->makeOrg();
        $borrower = $this->makeUser('borrower', $org);
        $admin = $this->makeUser('admin', $org);
        $asset = $this->makeAsset($org);

        $reservation = app(CreateReservation::class)->execute(
            $borrower,
            now()->addDay()->setTime(8, 0),
            now()->addDay()->setTime(10, 0),
            'Peminjaman proyektor untuk presentasi',
            [$asset->id],
        );

        return app(ApproveReservation::class)->execute($reservation, $admin);
    }

    public function test_checkin_returns_asset_to_available(): void
    {
        [$borrowing, $staff] = $this->activeBorrowing();
        $item = $borrowing->items->first();

        app(CheckinBorrowing::class)->execute(
            $borrowing,
            $staff,
            [$item->id => ['condition_in' => 'good', 'outcome' => 'ok']],
        );

        $fresh = $borrowing->fresh();

        $this->assertEquals(BorrowingStatus::Returned, $fresh->status);
        $this->assertNotNull($fresh->returned_at);
        $this->assertEquals($staff->id, $fresh->checked_in_by);
        $this->assertEquals(AssetStatus::Available, $item->asset->fresh()->status);
        $this->assertEquals('good', $item->fresh()->condition_in->value);
        $this->assertTrue(AssetInspection::where('borrowing_id', $borrowing->id)->where('stage', 'checkin')->exists());
        $this->assertFalse(Issue::where('asset_id', $item->asset_id)->exists());
    }

    public function test_damaged_outcome_creates_issue_and_marks_asset(): void
    {
        [$borrowing, $staff] = $this->activeBorrowing();
        $item = $borrowing->items->first();

        app(CheckinBorrowing::class)->execute(
            $borrowing,
            $staff,
            [$item->id => ['condition_in' => 'broken', 'outcome' => 'damaged', 'notes_in' => 'Layar retak terjatuh']],
        );

        $this->assertEquals(AssetStatus::Damaged, $item->asset->fresh()->status);
        $this->assertDatabaseHas('issues', ['asset_id' => $item->asset_id, 'type' => 'damage', 'status' => 'open']);
    }

    public function test_lost_outcome_creates_issue_and_marks_lost(): void
    {
        [$borrowing, $staff] = $this->activeBorrowing();
        $item = $borrowing->items->first();

        app(CheckinBorrowing::class)->execute(
            $borrowing,
            $staff,
            [$item->id => ['condition_in' => 'good', 'outcome' => 'lost', 'notes_in' => 'Tidak dikembalikan']],
        );

        $this->assertEquals(AssetStatus::Lost, $item->asset->fresh()->status);
        $this->assertDatabaseHas('issues', ['asset_id' => $item->asset_id, 'type' => 'loss']);
    }

    public function test_overdue_borrowing_can_still_be_checked_in(): void
    {
        [$borrowing, $staff] = $this->activeBorrowing();
        $item = $borrowing->items->first();

        $borrowing->update(['status' => BorrowingStatus::Overdue]);

        app(CheckinBorrowing::class)->execute(
            $borrowing,
            $staff,
            [$item->id => ['condition_in' => 'good', 'outcome' => 'ok']],
        );

        $this->assertEquals(BorrowingStatus::Returned, $borrowing->fresh()->status);
        $this->assertEquals(AssetStatus::Available, $item->asset->fresh()->status);
    }

    public function test_pending_borrowing_cannot_be_checked_in(): void
    {
        $org = $this->makeOrg();
        $staff = $this->makeUser('staff', $org);
        $borrowing = $this->pendingBorrowing();

        $this->expectException(ValidationException::class);
        app(CheckinBorrowing::class)->execute(
            $borrowing,
            $staff,
            [$borrowing->items->first()->id => ['condition_in' => 'good', 'outcome' => 'ok']],
        );
    }

    public function test_checkin_by_borrower_without_permission_is_forbidden(): void
    {
        $org = $this->makeOrg();
        $borrower = $this->makeUser('borrower', $org);
        $borrowing = $this->pendingBorrowing();

        $this->actingAs($borrower)
            ->post(route('admin.checkin.store', $borrowing), [])
            ->assertForbidden();
    }

    public function test_notes_wajib_diisi_bila_unit_rusak(): void
    {
        [$borrowing, $staff] = $this->activeBorrowing();
        $item = $borrowing->items->first();

        $this->actingAs($staff)
            ->post(route('admin.checkin.store', $borrowing), [
                'items' => [$item->id => ['condition_in' => 'broken', 'outcome' => 'damaged', 'notes_in' => '']],
            ])
            ->assertSessionHasErrors('items.'.$item->id.'.notes_in');
    }
}
