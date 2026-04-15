<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'plan_id', 'status', 'stripe_subscription_id',
        'promo_code_id', 'current_period_start', 'current_period_end',
        'cancelled_at', 'is_gift', 'gifted_by', 'gift_recipient_email',
    ];

    protected $casts = [
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'cancelled_at' => 'datetime',
        'is_gift' => 'boolean',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function plan() { return $this->belongsTo(Plan::class); }
    public function promoCode() { return $this->belongsTo(PromoCode::class); }
    public function giftedBy() { return $this->belongsTo(User::class, 'gifted_by'); }
    public function payments() { return $this->hasMany(Payment::class); }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'active'
            && $this->current_period_end
            && now()->gt($this->current_period_end);
    }

    public function getDaysOverdueAttribute(): int
    {
        if (!$this->is_overdue) return 0;
        return (int) now()->diffInDays($this->current_period_end);
    }
}
