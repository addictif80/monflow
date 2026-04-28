<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasUuids;

    protected $fillable = ['admin_id', 'action', 'target_type', 'target_id', 'details', 'ip_address'];
    protected $casts = ['details' => 'array'];

    public function admin() { return $this->belongsTo(User::class, 'admin_id'); }

    public static function record(string $action, $target = null, ?array $details = null): static
    {
        return static::create([
            'admin_id' => auth()->id(),
            'action' => $action,
            'target_type' => $target ? get_class($target) : null,
            'target_id' => $target?->id,
            'details' => $details,
            'ip_address' => request()->ip(),
        ]);
    }
}
