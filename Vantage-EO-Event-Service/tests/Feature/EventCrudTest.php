<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EventCrudTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private Venue $venue;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create(['name' => 'Concert']);
        $this->venue = Venue::create([
            'name' => 'Convention Hall',
            'address' => 'Yogyakarta',
            'capacity' => 500,
        ]);

        Http::fake(function (Request $request) {
            $token = str_replace('Bearer ', '', $request->header('Authorization')[0] ?? '');

            return match ($token) {
                'owner-token' => Http::response(['id' => 10, 'name' => 'Owner', 'role' => 'user']),
                'other-token' => Http::response(['id' => 20, 'name' => 'Other', 'role' => 'user']),
                'admin-token' => Http::response(['id' => 1, 'name' => 'Admin', 'role' => 'admin']),
                default => Http::response(['message' => 'Unauthenticated.'], 401),
            };
        });
    }

    public function test_user_can_create_and_manage_their_event(): void
    {
        $created = $this->withToken('owner-token')->postJson('/api/events', $this->payload());

        $created->assertCreated()
            ->assertJsonPath('data.creator_id', 10)
            ->assertJsonPath('data.creator_name', 'Owner')
            ->assertJsonPath('data.title', 'Vantage Conference');

        $eventId = $created->json('data.id');

        $this->withToken('owner-token')
            ->putJson("/api/events/{$eventId}", ['title' => 'Updated Conference'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated Conference');

        $this->withToken('owner-token')
            ->deleteJson("/api/events/{$eventId}")
            ->assertOk();

        $this->assertDatabaseMissing('events', ['id' => $eventId]);
    }

    public function test_user_cannot_manage_another_users_event_but_admin_can(): void
    {
        $event = Event::create([...$this->payload(), 'creator_id' => 10]);

        $this->withToken('other-token')
            ->putJson("/api/events/{$event->id}", ['title' => 'Stolen Event'])
            ->assertForbidden();

        $this->withToken('admin-token')
            ->putJson("/api/events/{$event->id}", ['title' => 'Admin Updated'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Admin Updated');
    }

    public function test_my_events_are_scoped_to_owner_while_admin_sees_all(): void
    {
        Event::create([...$this->payload(), 'creator_id' => 10]);
        Event::create([...$this->payload('Another Event'), 'creator_id' => 20]);

        $this->withToken('owner-token')
            ->getJson('/api/my-events')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.creator_id', 10);

        $this->withToken('admin-token')
            ->getJson('/api/my-events')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_public_index_only_contains_published_events(): void
    {
        Event::create([...$this->payload('Draft Event'), 'creator_id' => 10]);
        Event::create([...$this->payload('Published Event'), 'creator_id' => 10, 'status' => 'published']);

        $this->getJson('/api/events')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Published Event');
    }

    private function payload(string $title = 'Vantage Conference'): array
    {
        return [
            'category_id' => $this->category->id,
            'venue_id' => $this->venue->id,
            'title' => $title,
            'description' => 'A complete event description.',
            'event_date' => '2026-09-01',
            'start_time' => '09:00',
            'end_time' => '12:00',
            'banner' => null,
            'price' => 100000,
            'quota' => 100,
            'status' => 'draft',
        ];
    }
}
