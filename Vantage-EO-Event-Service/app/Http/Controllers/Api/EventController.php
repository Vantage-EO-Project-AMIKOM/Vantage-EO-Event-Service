<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => Event::with(['category', 'venue'])->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'venue_id' => ['required', 'integer', 'exists:venues,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'event_date' => ['required', 'date'],
            'start_time' => ['required'],
            'end_time' => ['required'],
            'banner' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'quota' => ['required', 'integer', 'min:1'],
            'status' => ['nullable', 'in:draft,published,completed,cancelled'],
        ]);

        $event = Event::create([
            ...$data,
            'creator_id' => $request->attributes->get('auth_user')['id'],
        ]);

        return response()->json([
            'message' => 'Event created successfully',
            'data' => $event,
        ], 201);
    }

    public function show(string $id)
    {
        return response()->json([
            'data' => Event::with(['category', 'venue'])->findOrFail($id),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $event = Event::findOrFail($id);
        $this->authorizeOwner($request, $event);

        $data = $request->validate([
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'venue_id' => ['sometimes', 'required', 'integer', 'exists:venues,id'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
            'event_date' => ['sometimes', 'required', 'date'],
            'start_time' => ['sometimes', 'required'],
            'end_time' => ['sometimes', 'required'],
            'banner' => ['nullable', 'string'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'quota' => ['sometimes', 'required', 'integer', 'min:1'],
            'status' => ['nullable', 'in:draft,published,completed,cancelled'],
        ]);

        $event->update($data);

        return response()->json([
            'message' => 'Event updated successfully',
            'data' => $event,
        ]);
    }

    public function destroy(Request $request, string $id)
    {
        $event = Event::findOrFail($id);
        $this->authorizeOwner($request, $event);
        $event->delete();

        return response()->json([
            'message' => 'Event deleted successfully',
        ]);
    }

    private function authorizeOwner(Request $request, Event $event): void
    {
        $user = $request->attributes->get('auth_user');
        abort_unless($user['role'] === 'admin' || (int) $event->creator_id === (int) $user['id'], 403, 'You can only manage your own events.');
    }
}
