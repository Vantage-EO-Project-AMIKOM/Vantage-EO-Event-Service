<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'ticket_request_id',
        'event_id',
        'user_id',
        'code',
        'status',
        'issued_at',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function request()
    {
        return $this->belongsTo(TicketRequest::class, 'ticket_request_id');
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
