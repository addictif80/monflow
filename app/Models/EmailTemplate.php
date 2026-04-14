<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use HasUuids;

    protected $fillable = ['template_type', 'subject', 'html_body', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
