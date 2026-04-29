<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PromoCode extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'code', 'discount_type', 'discount_value', 'max_uses',
        'current_uses', 'valid_from', 'valid_until', 'is_active',
        'is_recurring', 'recurring_months',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'is_active' => 'boolean',
        'is_recurring' => 'boolean',
    ];

    public function plans()
    {
        return $this->belongsToMany(Plan::class, 'plan_promo_code');
    }

    public function getIsValidAttribute(): bool
    {
        if (!$this->is_active) return false;
        if ($this->max_uses > 0 && $this->current_uses >= $this->max_uses) return false;
        if ($this->valid_until && now()->gt($this->valid_until)) return false;
        if (now()->lt($this->valid_from)) return false;
        return true;
    }
}
