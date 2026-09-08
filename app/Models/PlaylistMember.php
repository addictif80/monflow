<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlaylistMember extends Model
{
    protected $fillable = ['shared_playlist_id', 'user_id', 'role', 'member_nd_playlist_id'];

    public function sharedPlaylist()
    {
        return $this->belongsTo(SharedPlaylist::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
