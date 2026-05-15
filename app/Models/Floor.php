<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Floor extends Model
{
    public const STATUS_DRAFT    = 'draft';
    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
    ];

    protected $fillable = [
        'shop_id', 'name', 'order', 'layout', 'bg_url',
        'status', 'rejection_reason', 'is_locked',
        'submitted_at', 'reviewed_at', 'reviewed_by_id',
    ];

    protected $casts = [
        'layout'       => 'array',
        'order'        => 'integer',
        'is_locked'    => 'boolean',
        'submitted_at' => 'datetime',
        'reviewed_at'  => 'datetime',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    public function isApproved(): bool { return $this->status === self::STATUS_APPROVED; }
    public function isPending(): bool  { return $this->status === self::STATUS_PENDING; }
    public function isRejected(): bool { return $this->status === self::STATUS_REJECTED; }

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
