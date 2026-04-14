<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SmtpConfiguration extends Model
{
    use HasUuids;

    protected $fillable = [
        'name', 'host', 'port', 'username', 'password',
        'use_tls', 'use_ssl', 'from_email', 'from_name', 'is_active',
    ];

    protected $casts = ['use_tls' => 'boolean', 'use_ssl' => 'boolean', 'is_active' => 'boolean'];
}
