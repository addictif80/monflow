<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    use HasUuids;

    protected $fillable = ['restoration_fee'];

    protected $casts = ['restoration_fee' => 'decimal:2'];

    public static function current(): self
    {
        return static::first() ?? static::create(['restoration_fee' => 0]);
    }
}
