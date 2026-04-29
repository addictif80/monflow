<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasUuids;

    protected $table = 'feedbacks';

    protected $fillable = ['user_id', 'type', 'subject', 'body', 'status', 'admin_note', 'ticket_id'];

    public function user() { return $this->belongsTo(User::class); }
    public function ticket() { return $this->belongsTo(Ticket::class); }
}
