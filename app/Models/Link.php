<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Link extends Model
{
    protected $fillable = [
        'title',
        'description',
        'url',
        'type',
        'category',
        'is_official',
        'is_active',
    ];
}
