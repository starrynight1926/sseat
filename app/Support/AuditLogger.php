<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * Record an audit entry. Safe to call without a target.
     */
    public static function log(string $action, ?Model $target = null, array $meta = []): void
    {
        AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => $action,
            'target_type' => $target ? class_basename($target) : null,
            'target_id'   => $target?->getKey(),
            'meta'        => $meta ?: null,
            'ip'          => Request::ip(),
        ]);
    }
}
