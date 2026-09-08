<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SharedPlaylist extends Model
{
    use HasUuids;

    protected $fillable = ['owner_id', 'owner_nd_playlist_id', 'name', 'is_public'];

    protected $casts = ['is_public' => 'boolean'];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members()
    {
        return $this->hasMany(PlaylistMember::class);
    }

    public function collaborators()
    {
        return $this->hasMany(PlaylistMember::class)->where('role', 'collaborator');
    }

    public function subscribers()
    {
        return $this->hasMany(PlaylistMember::class)->where('role', 'subscriber');
    }
}
