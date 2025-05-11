<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{    

    protected $fillable = ['body', 'post_id', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function getEditableAttribute()
    {
        return $this->user_id == auth()->id() || (auth()->check() && auth()->user()->is_admin === '1');
    }

    public function getPinnedEditableAttribute()
    {
        return $this->user_id == auth()->id() || (auth()->check() && auth()->user()->is_admin === '1');
    }
}
