<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Chair extends Model
{
    protected $fillable = ['floor_id', 'external_id', 'type', 'label', 'status', 'pos_x', 'pos_y'];

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_OCCUPIED  = 'occupied';

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }
}
