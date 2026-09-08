<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class UrssafReport extends Model
{
    use HasUuids;

    protected $fillable = [
        'period_month', 'total_amount', 'transaction_count',
        'pdf_path', 'sent_to', 'sent_at', 'status', 'error',
    ];

    protected $casts = [
        'period_month' => 'date',
        'total_amount' => 'decimal:2',
        'sent_at' => 'datetime',
    ];
}
