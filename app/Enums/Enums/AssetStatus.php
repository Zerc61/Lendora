<?php
// app/Enums/AssetStatus.php  ← state machine inti (PDF bag. 9)
namespace App\Enums;

enum AssetStatus: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case Borrowed = 'borrowed';
    case Maintenance = 'maintenance';
    case Damaged = 'damaged';
    case Lost = 'lost';
    case Retired = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Tersedia',
            self::Reserved => 'Direservasi',
            self::Borrowed => 'Dipinjam',
            self::Maintenance => 'Maintenance',
            self::Damaged => 'Rusak',
            self::Lost => 'Hilang',
            self::Retired => 'Dipensiunkan',
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /** Transisi valid sesuai PDF bag. 9 */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Available   => [self::Reserved, self::Borrowed, self::Maintenance, self::Damaged, self::Lost, self::Retired],
            self::Reserved    => [self::Available, self::Borrowed, self::Retired],
            self::Borrowed    => [self::Available, self::Damaged, self::Lost, self::Maintenance],
            self::Maintenance => [self::Available, self::Damaged, self::Retired],
            self::Damaged     => [self::Maintenance, self::Available, self::Retired],
            self::Lost        => [self::Available, self::Retired], // recovered
            self::Retired     => [], // terminal state
        };
    }
}