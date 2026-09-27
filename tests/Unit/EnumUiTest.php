<?php

namespace Tests\Unit;

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Enums\AttachmentType;
use App\Enums\BorrowingStatus;
use App\Enums\InspectionStage;
use App\Enums\IssueSeverity;
use App\Enums\IssueStatus;
use App\Enums\IssueType;
use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceStatus;
use App\Enums\MaintenanceType;
use App\Enums\OrganizationStatus;
use App\Enums\ReservationStatus;
use App\Enums\UserStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Kontrak enum ↔ UI design system.
 *
 * Semua enum domain wajib punya label() Bahasa Indonesia dan tone() yang
 * berasal dari palet CSS, karena <x-status> merender keduanya.
 */
class EnumUiTest extends TestCase
{
    private const TONES = ['ok', 'info', 'brand', 'accent', 'warn', 'bad', 'orange', 'muted'];

    public static function enums(): array
    {
        return array_map(
            fn (string $enum) => [$enum],
            [
                AssetStatus::class,
                AssetCondition::class,
                BorrowingStatus::class,
                ReservationStatus::class,
                IssueStatus::class,
                IssueType::class,
                IssueSeverity::class,
                MaintenanceStatus::class,
                MaintenanceType::class,
                MaintenancePriority::class,
                AttachmentType::class,
                UserStatus::class,
                OrganizationStatus::class,
            ]
        );
    }

    #[DataProvider('enums')]
    public function test_enum_memiliki_label_dan_tone(string $enum): void
    {
        $labels = [];

        foreach ($enum::cases() as $case) {
            $this->assertTrue(method_exists($case, 'label'), "{$enum} tidak punya label().");
            $this->assertTrue(method_exists($case, 'tone'), "{$enum} tidak punya tone().");

            $label = $case->label();
            $tone = $case->tone();

            $this->assertIsString($label);
            $this->assertNotSame('', trim($label), "{$enum}::{$case->value} label kosong.");
            $this->assertMatchesRegularExpression(
                '/^\p{Lu}/u',
                $label,
                "{$enum}::{$case->value} label harus diawali huruf kapital."
            );

            $this->assertContains($tone, self::TONES, "{$enum}::{$case->value} tone tidak dikenal: {$tone}");

            $this->assertArrayNotHasKey($label, $labels, "{$enum} punya label duplikat: {$label}");
            $labels[$label] = true;
        }
    }

    public function test_asset_condition_score_selalu_0_sampai_100(): void
    {
        foreach (AssetCondition::cases() as $case) {
            $this->assertGreaterThanOrEqual(0, $case->score());
            $this->assertLessThanOrEqual(100, $case->score());
        }
    }

    public function test_inspection_stage_punya_label(): void
    {
        foreach (InspectionStage::cases() as $case) {
            $this->assertNotSame('', trim($case->label()));
        }
    }

    public function test_status_aset_menghormati_state_machine(): void
    {
        // Retired adalah terminal
        $this->assertSame([], AssetStatus::Retired->allowedTransitions());

        // Tersedia → dipinjam diperbolehkan, tersedia → rusak tidak langsung
        $this->assertTrue(AssetStatus::Available->canTransitionTo(AssetStatus::Borrowed));
        $this->assertTrue(AssetStatus::Available->canTransitionTo(AssetStatus::Damaged));

        // Aset yang sedang dipinjam tidak boleh "direservasi" tanpa dikembalikan dulu
        $this->assertFalse(AssetStatus::Borrowed->canTransitionTo(AssetStatus::Reserved));
        $this->assertTrue(AssetStatus::Borrowed->canTransitionTo(AssetStatus::Available));
    }
}
