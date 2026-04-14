<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasUuids;

    protected $fillable = [
        'name', 'description', 'price', 'billing_cycle',
        'stripe_price_id', 'max_devices', 'is_active', 'sort_order',
    ];

    protected $casts = ['is_active' => 'boolean', 'price' => 'decimal:2'];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function promoCodes()
    {
        return $this->belongsToMany(PromoCode::class, 'plan_promo_code');
    }

    public function getBillingLabelAttribute(): string
    {
        return match($this->billing_cycle) {
            'monthly' => 'mois',
            'quarterly' => 'trimestre',
            'yearly' => 'an',
            default => $this->billing_cycle,
        };
    }

    public function getPeriodDaysAttribute(): int
    {
        return match($this->billing_cycle) {
            'monthly' => 30,
            'quarterly' => 90,
            'yearly' => 365,
            default => 30,
        };
    }
}
