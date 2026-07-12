<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => Venue::latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'capacity' => ['required', 'integer', 'min:1'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $venue = Venue::create($data);

        return response()->json([
            'message' => 'Venue created successfully',
            'data' => $venue,
        ], 201);
    }

    public function show(string $id)
    {
        return response()->json([
            'data' => Venue::findOrFail($id),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $venue = Venue::findOrFail($id);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'address' => ['sometimes', 'required', 'string'],
            'capacity' => ['sometimes', 'required', 'integer', 'min:1'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $venue->update($data);

        return response()->json([
            'message' => 'Venue updated successfully',
            'data' => $venue,
        ]);
    }

    public function destroy(string $id)
    {
        $venue = Venue::findOrFail($id);
        $venue->delete();

        return response()->json([
            'message' => 'Venue deleted successfully',
        ]);
    }
}
