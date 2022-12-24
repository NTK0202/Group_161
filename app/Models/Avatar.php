<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Avatar extends Model
{
    use HasFactory;

    protected $fillable = [
        'image',
        'path',
        'avatarable_type',
        'avatarable_id',
    ];

    public function avatarable(){
        return $this->morphTo();
    }

}
