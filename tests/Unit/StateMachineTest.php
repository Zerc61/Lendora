<?php

namespace Tests\Unit;

use App\Enums\AssetStatus;
use App\Enums\IssueStatus;
use App\Enums\MaintenanceStatus;
use PHPUnit\Framework\TestCase;

/**
 * State machine murni enum — tanpa database.
 *
 * Aturan transisi di PDF bag. 9 diuji langsung di enum-nya, jadi pelanggaran
 * transisi terdeteksi sebelum menyentuh controller.
 */
class StateMachineTest extends TestCase
{
    // ── Asset (PDF bag. 9) ──
    public function test_asset_borrowed_cannot_jump_to_retired(): void
    {
        $this->assertFalse(AssetStatus::Borrowed->canTransitionTo(AssetStatus::Retired));
    }

    public function test_asset_available_can_go_reserved(): void
    {
        $this->assertTrue(AssetStatus::Available->canTransitionTo(AssetStatus::Reserved));
    }

    public function test_asset_borrowed_can_be_returned_or_flagged(): void
    {
        $allowed = AssetStatus::Borrowed->allowedTransitions();

        $this->assertContains(AssetStatus::Available, $allowed);
        $this->assertContains(AssetStatus::Damaged, $allowed);
        $this->assertContains(AssetStatus::Lost, $allowed);
        $this->assertContains(AssetStatus::Maintenance, $allowed);
    }

    public function test_asset_retired_is_terminal(): void
    {
        $this->assertSame([], AssetStatus::Retired->allowedTransitions());
    }

    public function test_lost_asset_can_be_recovered_or_retired(): void
    {
        $this->assertTrue(AssetStatus::Lost->canTransitionTo(AssetStatus::Available)); // recovered (PDF 5.4)
        $this->assertTrue(AssetStatus::Lost->canTransitionTo(AssetStatus::Retired));
    }

    // ── Maintenance (PDF bag. 9) ──
    public function test_maintenance_must_follow_workflow_order(): void
    {
        $this->assertFalse(MaintenanceStatus::Open->canTransitionTo(MaintenanceStatus::Completed));
        $this->assertTrue(MaintenanceStatus::Open->canTransitionTo(MaintenanceStatus::Assigned));
        $this->assertTrue(MaintenanceStatus::InProgress->canTransitionTo(MaintenanceStatus::WaitingParts));
        $this->assertTrue(MaintenanceStatus::Completed->canTransitionTo(MaintenanceStatus::Verified));
    }

    public function test_maintenance_verified_is_terminal(): void
    {
        $this->assertSame([], MaintenanceStatus::Verified->allowedTransitions());
    }

    // ── Issue (PDF bag. 9) ──
    public function test_issue_open_must_be_investigated_first(): void
    {
        $this->assertFalse(IssueStatus::Open->canTransitionTo(IssueStatus::Resolved));
        $this->assertTrue(IssueStatus::Open->canTransitionTo(IssueStatus::Investigating));
        $this->assertTrue(IssueStatus::Investigating->canTransitionTo(IssueStatus::Resolved));
        $this->assertTrue(IssueStatus::Investigating->canTransitionTo(IssueStatus::Closed));
    }
}
