<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function chairs(): HasMany
    {
        return $this->hasMany(Chair::class);
    }

    /**
     * Sync DB chair rows with the chair-typed objects from the layout JSON.
     * Preserves existing status; deletes chairs no longer present.
     */
    public function syncChairsFromLayout(): void
    {
        $layout = $this->layout ?? [];
        $objects = is_array($layout['objects'] ?? null) ? $layout['objects'] : [];
        $chairTypes = ['chair', 'chair-round'];
        $present = [];

        foreach ($objects as $o) {
            $type = $o['type'] ?? null;
            $extId = $o['id'] ?? null;
            if (!in_array($type, $chairTypes, true) || !$extId) continue;
            $present[] = $extId;
            $this->chairs()->updateOrCreate(
                ['external_id' => $extId],
                [
                    'type' => $type,
                    'label' => $o['label'] ?? null,
                    'pos_x' => (float)($o['x'] ?? 0),
                    'pos_y' => (float)($o['y'] ?? 0),
                ]
            );
        }
        // Remove chairs no longer in layout
        $this->chairs()->whereNotIn('external_id', $present ?: [''])->delete();
    }
}
