<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardAnalyticsController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\TicketRequestController;
use App\Http\Controllers\Api\VenueController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('categories', CategoryController::class);

Route::apiResource('venues', VenueController::class);

Route::apiResource('events', EventController::class)->only(['index', 'show']);

Route::middleware('auth.service')->group(function () {
    Route::get('my-events', [EventController::class, 'mine']);
    Route::apiResource('events', EventController::class)->only(['store', 'update', 'destroy']);

    Route::get('ticket-requests', [TicketRequestController::class, 'index']);
    Route::post('events/{event}/ticket-requests', [TicketRequestController::class, 'store']);
    Route::get('events/{event}/ticket-requests', [TicketRequestController::class, 'eventIndex']);
    Route::patch('ticket-requests/{ticketRequest}/approve', [TicketRequestController::class, 'approve']);
    Route::patch('ticket-requests/{ticketRequest}/reject', [TicketRequestController::class, 'reject']);
    Route::delete('ticket-requests/{ticketRequest}', [TicketRequestController::class, 'destroy']);

    Route::get('tickets', [TicketController::class, 'index']);
    Route::get('events/{event}/tickets', [TicketController::class, 'eventIndex']);
    Route::get('tickets/{ticket}', [TicketController::class, 'show']);
    Route::patch('tickets/{ticket}', [TicketController::class, 'update']);
});

Route::get('dashboard/analytics', DashboardAnalyticsController::class);
