<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class UserDevice extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'device_name', 'device_type', 'ip_address',
        'user_agent', 'session_key', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean', 'last_active' => 'datetime', 'created_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }
}
