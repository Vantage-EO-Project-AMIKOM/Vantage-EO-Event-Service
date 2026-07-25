<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TicketRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->attributes->get('auth_user');

        return response()->json([
            'data' => TicketRequest::with(['event', 'tickets'])
                ->where('user_id', $user['id'])
                ->latest()
                ->get(),
        ]);
    }

    public function eventIndex(Request $request, Event $event)
    {
        $this->authorizeOwner($request, $event);

        return response()->json([
            'data' => $event->ticketRequests()
                ->with('tickets')
                ->latest()
                ->get(),
        ]);
    }

    public function store(Request $request, Event $event)
    {
        $user = $request->attributes->get('auth_user');
        abort_if((int) $event->creator_id === (int) $user['id'], 422, 'Event owners cannot request tickets for their own event.');
        abort_unless($event->status === 'published', 422, 'Tickets can only be requested for published events.');

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.$event->quota],
        ]);

        $hasOpenRequest = TicketRequest::where('event_id', $event->id)
            ->where('user_id', $user['id'])
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($hasOpenRequest) {
            throw ValidationException::withMessages([
                'event' => 'You already have a pending or approved ticket request for this event.',
            ]);
        }

        if ($data['quantity'] > $event->remaining_quota) {
            throw ValidationException::withMessages([
                'quantity' => "Only {$event->remaining_quota} ticket(s) remain.",
            ]);
        }

        $ticketRequest = TicketRequest::create([
            ...$data,
            'event_id' => $event->id,
            'user_id' => $user['id'],
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Ticket request submitted successfully and is awaiting owner approval.',
            'data' => $ticketRequest,
        ], 201);
    }

    public function approve(Request $request, TicketRequest $ticketRequest)
    {
        $user = $request->attributes->get('auth_user');
        $this->authorizeOwner($request, $ticketRequest->event);

        $approvedRequest = DB::transaction(function () use ($ticketRequest, $user) {
            $lockedRequest = TicketRequest::query()->lockForUpdate()->findOrFail($ticketRequest->id);
            $event = Event::query()->lockForUpdate()->findOrFail($lockedRequest->event_id);

            if ($lockedRequest->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => 'Only pending ticket requests can be approved.',
                ]);
            }

            $issued = Ticket::where('event_id', $event->id)
                ->where('status', '!=', 'cancelled')
                ->count();
            $remaining = max(0, $event->quota - $issued);

            if ($lockedRequest->quantity > $remaining) {
                throw ValidationException::withMessages([
                    'quantity' => "This request needs {$lockedRequest->quantity} ticket(s), but only {$remaining} remain.",
                ]);
            }

            for ($number = 0; $number < $lockedRequest->quantity; $number++) {
                Ticket::create([
                    'ticket_request_id' => $lockedRequest->id,
                    'event_id' => $event->id,
                    'user_id' => $lockedRequest->user_id,
                    'code' => (string) Str::uuid(),
                    'status' => 'valid',
                    'issued_at' => now(),
                ]);
            }

            $lockedRequest->update([
                'status' => 'approved',
                'decided_by' => $user['id'],
                'decided_at' => now(),
                'rejection_reason' => null,
            ]);

            return $lockedRequest->load(['event', 'tickets']);
        });

        return response()->json([
            'message' => 'Ticket request approved and tickets issued successfully.',
            'data' => $approvedRequest,
        ]);
    }

    public function reject(Request $request, TicketRequest $ticketRequest)
    {
        $this->authorizeOwner($request, $ticketRequest->event);
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        if ($ticketRequest->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'Only pending ticket requests can be rejected.',
            ]);
        }

        $user = $request->attributes->get('auth_user');
        $ticketRequest->update([
            'status' => 'rejected',
            'decided_by' => $user['id'],
            'decided_at' => now(),
            'rejection_reason' => $data['reason'] ?? null,
        ]);

        return response()->json([
            'message' => 'Ticket request rejected.',
            'data' => $ticketRequest->fresh(),
        ]);
    }

    public function destroy(Request $request, TicketRequest $ticketRequest)
    {
        $user = $request->attributes->get('auth_user');
        abort_unless((int) $ticketRequest->user_id === (int) $user['id'], 403, 'You can only cancel your own request.');

        if ($ticketRequest->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'Only pending ticket requests can be cancelled.',
            ]);
        }

        $ticketRequest->delete();

        return response()->json(['message' => 'Ticket request cancelled successfully.']);
    }

    private function authorizeOwner(Request $request, Event $event): void
    {
        $user = $request->attributes->get('auth_user');
        abort_unless(
            $user['role'] === 'admin' || (int) $event->creator_id === (int) $user['id'],
            403,
            'Only the event owner can manage its ticket requests.'
        );
    }
}
