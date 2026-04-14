<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'subject', 'category', 'priority', 'status', 'assigned_to', 'closed_at'];

    protected $casts = ['closed_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }
    public function assignedTo() { return $this->belongsTo(User::class, 'assigned_to'); }
    public function messages() { return $this->hasMany(TicketMessage::class)->orderBy('created_at'); }
}
