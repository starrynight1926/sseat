<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Floor extends Model
{
    protected $fillable = ['shop_id', 'name', 'order', 'layout', 'bg_url'];

    protected $casts = [
        'layout' => 'array',
        'order' => 'integer',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
