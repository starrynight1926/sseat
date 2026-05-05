<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Shop extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'user_id'];

    public function owner()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    protected static function booted(): void
    {
        static::creating(function (Shop $shop) {
            if (empty($shop->slug)) {
                $base = Str::slug($shop->name) ?: 'shop';
                $slug = $base;
                $i = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $base . '-' . $i++;
                }
                $shop->slug = $slug;
            }
        });
    }

    public function floors(): HasMany
    {
        return $this->hasMany(Floor::class)->orderBy('order')->orderBy('id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
