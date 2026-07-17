<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use Illuminate\Support\Carbon;

class DashboardAnalyticsController extends Controller
{
    public function __invoke()
    {
        $today = Carbon::today();
        $events = Event::query()->with(['category', 'venue'])->get();
        $upcoming = $events
            ->filter(fn (Event $event) => $event->event_date->greaterThanOrEqualTo($today)
                && in_array($event->status, ['draft', 'published'], true))
            ->sortBy('event_date')
            ->values();
        $categoryCounts = $events->countBy('category_id');
        $totalEvents = $events->count();

        return response()->json([
            'data' => [
                'summary' => [
                    'active_events' => $events->where('status', 'published')
                        ->filter(fn (Event $event) => $event->event_date->greaterThanOrEqualTo($today))
                        ->count(),
                    'upcoming_events' => $upcoming->count(),
                    'total_capacity' => (int) $events->sum('quota'),
                    'total_events' => $totalEvents,
                ],
                'monthly_events' => $events
                    ->groupBy(fn (Event $event) => $event->event_date->format('Y-m'))
                    ->sortKeys()
                    ->map(fn ($monthEvents, $month) => [
                        'month' => $month,
                        'label' => Carbon::createFromFormat('Y-m', $month)->translatedFormat('M'),
                        'count' => $monthEvents->count(),
                    ])
                    ->values(),
                'categories' => Category::query()->orderBy('name')->get()->map(function (Category $category) use ($categoryCounts, $totalEvents) {
                    $count = $categoryCounts->get($category->id, 0);

                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'count' => $count,
                        'percentage' => $totalEvents ? round(($count / $totalEvents) * 100, 1) : 0,
                    ];
                })->values(),
                'upcoming_events' => $upcoming->take(5)->map(fn (Event $event) => [
                    'id' => $event->id,
                    'title' => $event->title,
                    'event_date' => $event->event_date->toDateString(),
                    'venue' => $event->venue?->name,
                    'quota' => $event->quota,
                    'status' => $event->status,
                ])->values(),
            ],
        ]);
    }
}
