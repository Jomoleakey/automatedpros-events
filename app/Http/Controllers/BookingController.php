<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Ticket;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Notifications\BookingConfirmed; // ADDED: Required for notification dispatch

class BookingController extends Controller
{
    /**
     * Display a listing of the user's bookings. (Customer/Organizer/Admin)
     */
    public function index()
    {
        $user = Auth::user();

        // Admin sees all, Organizer sees bookings for their events, Customer sees their own
        if ($user->role === 'admin') {
            $bookings = Booking::with(['ticket.event', 'user'])->get();
        } elseif ($user->role === 'organizer') {
            $eventIds = $user->events->pluck('id');
            $bookings = Booking::whereHas('ticket.event', function ($query) use ($eventIds) {
                $query->whereIn('id', $eventIds);
            })->with(['ticket.event', 'user'])->get();
        } else {
            // Customer
            $bookings = $user->bookings()->with(['ticket.event', 'payment'])->get();
        }

        return response(['bookings' => $bookings], 200);
    }

    /**
     * Store a newly created booking. (Customer only)
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Authorization Check: Only Customers can make new bookings
        if ($user->role !== 'customer') {
            return response(['message' => 'Only customers can make bookings.'], 403);
        }

        $fields = $request->validate([
            'ticket_id' => 'required|exists:tickets,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $ticket = Ticket::find($fields['ticket_id']);

        // Check availability
        if ($ticket->quantity_available < $fields['quantity']) {
            throw ValidationException::withMessages([
                'quantity' => 'The requested quantity exceeds the available tickets.'
            ]);
        }

        // Calculate total price
        $totalPrice = $ticket->price * $fields['quantity'];

        // Create the booking
        $booking = Booking::create([
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'quantity' => $fields['quantity'],
            'total_price' => $totalPrice,
            'status' => 'pending', // Pending until payment is processed
        ]);

        return response([
            'booking' => $booking->load('ticket.event'),
            'message' => 'Booking created. Proceed to payment.'
        ], 201);
    }

    /**
     * Display the specified booking. (User can only view their own booking)
     */
    public function show(Booking $booking)
    {
        $user = Auth::user();

        // Authorization Check: Admin can see any; Customer can only see their own
        if ($user->role !== 'admin' && $booking->user_id !== $user->id) {
            return response(['message' => 'Unauthorized to view this booking.'], 403);
        }

        return response(['booking' => $booking->load(['ticket.event', 'payment'])], 200);
    }

    /**
     * Process payment for a pending booking.
     */
    public function processPayment(Request $request, Booking $booking)
    {
        $user = Auth::user();

        // Authorization Check: Only the booking owner (customer) can pay
        if ($user->role !== 'customer' || $booking->user_id !== $user->id) {
            return response(['message' => 'Unauthorized to process payment for this booking.'], 403);
        }

        // Check if already paid
        if ($booking->status === 'confirmed') {
            return response(['message' => 'Payment already confirmed for this booking.'], 400);
        }

        $fields = $request->validate([
            'method' => 'required|string|max:255',
        ]);

        // IMPORTANT: Ensure $user is included in the use closure since we need it for notification
        return DB::transaction(function () use ($booking, $fields, $user) { 
            $ticket = $booking->ticket;

            // 1. Simulate Payment (always successful for this test)
            $payment = Payment::create([
                'booking_id' => $booking->id,
                'amount' => $booking->total_price,
                'method' => $fields['method'],
                'status' => 'completed',
                'transaction_id' => uniqid('txn_'),
            ]);

            // 2. Update Booking Status
            $booking->status = 'confirmed';
            $booking->save();

            // 3. Update Ticket Availability (Reduce stock)
            $ticket->quantity_available -= $booking->quantity;
            $ticket->save();

            // 4. Dispatch the Notification (NEW STEP)
            // The User model needs the Notifiable trait, which you already added in Section 1.
            // The notification will be processed by the queue listener.
            $user->notify(new BookingConfirmed($booking));

            return response([
                'booking' => $booking->load(['ticket.event', 'payment']),
                'message' => 'Payment successful. Booking confirmed.'
            ], 200);
        });
    }
}