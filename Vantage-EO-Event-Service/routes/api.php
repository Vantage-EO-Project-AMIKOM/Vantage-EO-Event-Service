<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\VenueController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\DashboardAnalyticsController;
use App\Http\Controllers\Api\TicketRequestController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('categories', CategoryController::class);

Route::apiResource('venues', VenueController::class);

Route::apiResource('events', EventController::class)->only(['index', 'show']);

Route::middleware('auth.service')->group(function () {
    Route::apiResource('events', EventController::class)->only(['store', 'update', 'destroy']);
    Route::get('ticket-requests', [TicketRequestController::class, 'index']);
    Route::post('events/{event}/ticket-requests', [TicketRequestController::class, 'store']);
});

Route::get('dashboard/analytics', DashboardAnalyticsController::class);
