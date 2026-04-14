<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['wallet_id', 'type', 'amount', 'description', 'stripe_payment_intent_id'];

    protected $casts = ['created_at' => 'datetime'];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }
}
