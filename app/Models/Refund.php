<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    use HasUuids;

    protected $fillable = [
        'payment_id', 'amount', 'reason', 'status',
        'refund_to', 'stripe_refund_id', 'processed_by',
    ];

    public function payment() { return $this->belongsTo(Payment::class); }
    public function processedBy() { return $this->belongsTo(User::class, 'processed_by'); }
}
