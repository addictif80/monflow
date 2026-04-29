<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Newsletter extends Model
{
    use HasUuids;

    protected $fillable = ['subject', 'html_body', 'status', 'recipients_count', 'sent_at'];

    protected $casts = ['sent_at' => 'datetime'];
}
