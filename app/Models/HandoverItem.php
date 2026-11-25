<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HandoverItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['handover_id', 'item_name', 'condition'];

    public function handover(): BelongsTo
    {
        return $this->belongsTo(Handover::class);
    }
}
