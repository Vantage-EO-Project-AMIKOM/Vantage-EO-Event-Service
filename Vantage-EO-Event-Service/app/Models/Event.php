<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $appends = ['issued_tickets_count', 'remaining_quota'];

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

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function getIssuedTicketsCountAttribute(): int
    {
        return (int) ($this->attributes['issued_tickets_count']
            ?? $this->tickets()->where('status', '!=', 'cancelled')->count());
    }

    public function getRemainingQuotaAttribute(): int
    {
        return max(0, (int) $this->quota - $this->issued_tickets_count);
    }
}
