<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TicketMessage extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['ticket_id', 'author_id', 'body', 'is_staff_reply'];

    protected $casts = ['created_at' => 'datetime', 'is_staff_reply' => 'boolean'];

    public function ticket() { return $this->belongsTo(Ticket::class); }
    public function author() { return $this->belongsTo(User::class, 'author_id'); }
}
