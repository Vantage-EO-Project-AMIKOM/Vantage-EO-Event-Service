<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\TicketRequest;
use Illuminate\Http\Request;

class TicketRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->attributes->get('auth_user');

        $requests = TicketRequest::with('event')
            ->when($user['role'] !== 'admin', fn ($query) => $query->where('user_id', $user['id']))
            ->latest()
            ->get();

        return response()->json(['data' => $requests]);
    }

    public function store(Request $request, Event $event)
    {
        $user = $request->attributes->get('auth_user');
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.$event->quota],
        ]);

        $ticketRequest = TicketRequest::create([
            ...$data,
            'event_id' => $event->id,
            'user_id' => $user['id'],
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Ticket request submitted successfully',
            'data' => $ticketRequest,
        ], 201);
    }
}
