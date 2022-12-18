<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Qa extends Model
{
    use HasFactory;

    protected $table = 'qa';

    protected $fillable = [
        'title',
        'content',
        'tag_id',
        'user_id',
    ];

    protected $hidden = [
        'tag_id',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function tag()
    {
        return $this->belongsTo(Tag::class, 'tag_id');
    }
}
