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
        'is_official',
        'sort_order',
        'is_active',
    ];
}
