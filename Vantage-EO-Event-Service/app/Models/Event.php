<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'category_id',
        'venue_id',
        'title',
        'description',
        'event_date',
        'start_time',
        'end_time',
        'banner',
        'price',
        'quota',
        'status',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }
}
