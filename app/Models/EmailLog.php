<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    protected $fillable = ['to', 'subject', 'template_type', 'html_body', 'status', 'error'];
}
