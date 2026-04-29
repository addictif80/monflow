<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'type', 'title', 'body', 'link', 'read_at'];
    protected $casts = ['read_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }
    public function scopeUnread($q) { return $q->whereNull('read_at'); }

    public static function send(string $userId, string $type, string $title, string $body, ?string $link = null): static
    {
        return static::create(['user_id' => $userId, 'type' => $type, 'title' => $title, 'body' => $body, 'link' => $link]);
    }
}
