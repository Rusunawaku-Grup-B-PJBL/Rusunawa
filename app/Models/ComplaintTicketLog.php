<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintTicketLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['author_id', 'status', 'note'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(ComplaintTicket::class, 'complaint_ticket_id');
    }
}
