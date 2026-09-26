<?php
// app/Models/MaintenanceLog.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceLog extends Model
{
    protected $fillable = ['maintenance_ticket_id', 'user_id', 'action', 'note', 'cost', 'performed_at'];

    protected function casts(): array
    {
        return ['cost' => 'decimal:2', 'performed_at' => 'datetime'];
    }

    public function ticket(): BelongsTo { return $this->belongsTo(MaintenanceTicket::class, 'maintenance_ticket_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}