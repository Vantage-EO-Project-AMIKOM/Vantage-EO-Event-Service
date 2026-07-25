<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketRequest;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TicketWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create(['name' => 'Conference']);
        $venue = Venue::create([
            'name' => 'Main Hall',
            'address' => 'Yogyakarta',
            'capacity' => 100,
        ]);

        $this->event = Event::create([
            'category_id' => $category->id,
            'venue_id' => $venue->id,
            'creator_id' => 10,
            'creator_name' => 'Owner',
            'title' => 'Vantage Summit',
            'description' => 'An event with owner-approved ticketing.',
            'event_date' => '2026-09-01',
            'start_time' => '09:00',
            'end_time' => '12:00',
            'price' => 100000,
            'quota' => 3,
            'status' => 'published',
        ]);

        Http::fake(function (Request $request) {
            $token = str_replace('Bearer ', '', $request->header('Authorization')[0] ?? '');

            return match ($token) {
                'owner-token' => Http::response(['id' => 10, 'name' => 'Owner', 'role' => 'user']),
                'requester-token' => Http::response(['id' => 20, 'name' => 'Requester', 'role' => 'user']),
                'second-token' => Http::response(['id' => 30, 'name' => 'Second', 'role' => 'user']),
                'admin-token' => Http::response(['id' => 1, 'name' => 'Admin', 'role' => 'admin']),
                default => Http::response(['message' => 'Unauthenticated.'], 401),
            };
        });
    }

    public function test_user_requests_and_owner_approval_issues_one_ticket_per_quantity(): void
    {
        $created = $this->withToken('requester-token')
            ->postJson("/api/events/{$this->event->id}/ticket-requests", $this->requestPayload(2))
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending');

        $requestId = $created->json('data.id');

        $this->withToken('requester-token')
            ->patchJson("/api/ticket-requests/{$requestId}/approve")
            ->assertForbidden();

        $this->withToken('owner-token')
            ->patchJson("/api/ticket-requests/{$requestId}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonCount(2, 'data.tickets');

        $this->assertDatabaseCount('tickets', 2);
        $this->assertDatabaseHas('ticket_requests', [
            'id' => $requestId,
            'status' => 'approved',
            'decided_by' => 10,
        ]);

        $this->getJson("/api/events/{$this->event->id}")
            ->assertOk()
            ->assertJsonPath('data.issued_tickets_count', 2)
            ->assertJsonPath('data.remaining_quota', 1);

        $this->withToken('requester-token')
            ->getJson('/api/tickets')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_approval_cannot_oversell_event_quota(): void
    {
        $first = $this->createRequest(20, 2);
        $second = $this->createRequest(30, 2);

        $this->withToken('owner-token')
            ->patchJson("/api/ticket-requests/{$first->id}/approve")
            ->assertOk();

        $this->withToken('owner-token')
            ->patchJson("/api/ticket-requests/{$second->id}/approve")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');

        $this->assertDatabaseCount('tickets', 2);
        $this->assertDatabaseHas('ticket_requests', ['id' => $second->id, 'status' => 'pending']);
    }

    public function test_owner_can_reject_and_user_can_cancel_a_pending_request(): void
    {
        $rejected = $this->createRequest(20, 1);

        $this->withToken('owner-token')
            ->patchJson("/api/ticket-requests/{$rejected->id}/reject", ['reason' => 'Registration closed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.rejection_reason', 'Registration closed');

        $pending = $this->createRequest(30, 1);
        $this->withToken('second-token')
            ->deleteJson("/api/ticket-requests/{$pending->id}")
            ->assertOk();

        $this->assertDatabaseMissing('ticket_requests', ['id' => $pending->id]);
    }

    public function test_owner_can_check_in_ticket_and_quota_cannot_drop_below_issued_count(): void
    {
        $ticketRequest = $this->createRequest(20, 2);
        $this->withToken('owner-token')->patchJson("/api/ticket-requests/{$ticketRequest->id}/approve");
        $ticket = Ticket::firstOrFail();

        $this->withToken('owner-token')
            ->patchJson("/api/tickets/{$ticket->id}", ['status' => 'used'])
            ->assertOk()
            ->assertJsonPath('data.status', 'used');

        $this->withToken('owner-token')
            ->putJson("/api/events/{$this->event->id}", ['quota' => 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quota');
    }

    private function createRequest(int $userId, int $quantity): TicketRequest
    {
        return TicketRequest::create([
            'event_id' => $this->event->id,
            'user_id' => $userId,
            'full_name' => "User {$userId}",
            'email' => "user{$userId}@example.com",
            'phone' => '08123456789',
            'quantity' => $quantity,
            'status' => 'pending',
        ]);
    }

    private function requestPayload(int $quantity): array
    {
        return [
            'full_name' => 'Ticket Requester',
            'email' => 'requester@example.com',
            'phone' => '08123456789',
            'quantity' => $quantity,
        ];
    }
}
