<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->attributes->get('auth_user');
        $query = Ticket::with(['event', 'request'])->latest('issued_at');

        return response()->json([
            'data' => $user['role'] === 'admin'
                ? $query->get()
                : $query->where('user_id', $user['id'])->get(),
        ]);
    }

    public function eventIndex(Request $request, Event $event)
    {
        $this->authorizeOwner($request, $event);

        return response()->json([
            'data' => $event->tickets()
                ->with('request')
                ->latest('issued_at')
                ->get(),
        ]);
    }

    public function show(Request $request, Ticket $ticket)
    {
        $this->authorizeHolderOrOwner($request, $ticket);

        return response()->json([
            'data' => $ticket->load(['event', 'request']),
        ]);
    }

    public function update(Request $request, Ticket $ticket)
    {
        $this->authorizeOwner($request, $ticket->event);
        $data = $request->validate([
            'status' => ['required', 'in:used,cancelled'],
        ]);

        if ($ticket->status !== 'valid') {
            throw ValidationException::withMessages([
                'status' => 'Only a valid ticket can be used or cancelled.',
            ]);
        }

        $ticket->update([
            'status' => $data['status'],
            'used_at' => $data['status'] === 'used' ? now() : null,
        ]);

        return response()->json([
            'message' => $data['status'] === 'used' ? 'Ticket checked in successfully.' : 'Ticket cancelled successfully.',
            'data' => $ticket->fresh(),
        ]);
    }

    private function authorizeHolderOrOwner(Request $request, Ticket $ticket): void
    {
        $user = $request->attributes->get('auth_user');
        abort_unless(
            $user['role'] === 'admin'
                || (int) $ticket->user_id === (int) $user['id']
                || (int) $ticket->event->creator_id === (int) $user['id'],
            403,
            'You cannot view this ticket.'
        );
    }

    private function authorizeOwner(Request $request, Event $event): void
    {
        $user = $request->attributes->get('auth_user');
        abort_unless(
            $user['role'] === 'admin' || (int) $event->creator_id === (int) $user['id'],
            403,
            'Only the event owner can manage this ticket.'
        );
    }
}
