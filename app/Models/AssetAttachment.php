<?php
// app/Models/AssetAttachment.php
namespace App\Models;

use App\Enums\AttachmentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetAttachment extends Model
{
    protected $fillable = ['asset_id', 'uploaded_by', 'file_path', 'type', 'title', 'metadata'];

    protected function casts(): array
    {
        return ['type' => AttachmentType::class, 'metadata' => 'array'];
    }

    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }
}