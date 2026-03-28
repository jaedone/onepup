<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Suggestion extends Model
{
    protected $fillable = [
        'message',
        'wants_notification',
        'email',
        'status',
    ];
}
