<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketRequest extends Model
{
    protected $fillable = [
        'event_id',
        'user_id',
        'full_name',
        'email',
        'phone',
        'quantity',
        'status',
        'decided_by',
        'decided_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'decided_at' => 'datetime',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
