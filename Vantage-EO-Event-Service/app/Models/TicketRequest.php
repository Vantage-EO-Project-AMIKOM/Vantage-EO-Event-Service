<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketRequest extends Model
{
    protected $fillable = ['event_id', 'user_id', 'full_name', 'email', 'phone', 'quantity', 'status'];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
