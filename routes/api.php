<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\BookingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// --- PUBLIC ROUTES (No Auth Required) ---
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// --- PROTECTED ROUTES (Requires Sanctum Auth) ---
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Event Routes: Resource for CRUD operations
    Route::apiResource('events', EventController::class); 
    
    // NEW: Route to handle ticket booking for a specific event
    Route::post('/events/{event}/book', [EventController::class, 'book']);
    
    // Booking Routes
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::post('/bookings/{booking}/pay', [BookingController::class, 'processPayment']);
    
    // User Info
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});