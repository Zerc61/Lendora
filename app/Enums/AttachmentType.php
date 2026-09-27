<?php
// app/Enums/AttachmentType.php
namespace App\Enums;

enum AttachmentType: string
{
    case Photo = 'photo';
    case Video = 'video';
    case Manual = 'manual';
    case Document = 'document';

    public function label(): string
    {
        return match ($this) {
            self::Photo => 'Foto',
            self::Video => 'Video',
            self::Manual => 'Manual',
            self::Document => 'Dokumen',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Photo => 'accent',
            self::Video => 'brand',
            self::Manual, self::Document => 'muted',
        };
    }

    public function isMedia(): bool
    {
        return $this === self::Photo || $this === self::Video;
    }
}
