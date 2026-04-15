<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'subscription_id', 'amount', 'wallet_amount', 'stripe_amount',
        'status', 'payment_method', 'stripe_payment_intent_id',
        'stripe_invoice_id', 'description',
    ];

    protected $casts = ['amount' => 'decimal:2'];

    public function user() { return $this->belongsTo(User::class); }
    public function subscription() { return $this->belongsTo(Subscription::class); }
    public function refunds() { return $this->hasMany(Refund::class); }
}
