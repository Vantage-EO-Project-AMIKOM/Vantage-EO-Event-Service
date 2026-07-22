<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected function casts(): array
    {
        return [
            'event_date' => 'date',
        ];
    }

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
        'creator_id',
        'creator_name',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function ticketRequests()
    {
        return $this->hasMany(TicketRequest::class);
    }
}
