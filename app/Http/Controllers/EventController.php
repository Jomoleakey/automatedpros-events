<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Models\Event;
use App\Models\Ticket;

class EventController extends Controller
{
    /**
     * Store a newly created event and its tickets.
     */
    public function store(Request $request)
    {
        // Validate incoming request
        $fields = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'location' => 'nullable|string|max:255',
            'price' => 'nullable|numeric',
            'capacity' => 'nullable|integer',
            'tickets' => 'sometimes|array',
            'tickets.*.name' => 'required_with:tickets|string|max:255',
            'tickets.*.price' => 'required_with:tickets|numeric',
            'tickets.*.quantity' => 'required_with:tickets|integer|min:0',
        ]);

        try {
            $event = DB::transaction(function () use ($fields) {
                // Create the Event
                $event = Event::create([
                    'title' => $fields['title'],
                    'description' => $fields['description'] ?? null,
                    'date' => $fields['date'],
                    'location' => $fields['location'] ?? null,
                    'price' => $fields['price'] ?? null,
                    'capacity' => $fields['capacity'] ?? null,
                ]);

                // Defensive mapping for tickets to match DB column names
                $tickets = $fields['tickets'] ?? [];

                foreach ($tickets as $ticketData) {
                    $ticketPayload = [];

                    // name / type mapping
                    if (Schema::hasColumn('tickets', 'name')) {
                        $ticketPayload['name'] = $ticketData['name'] ?? null;
                    } elseif (Schema::hasColumn('tickets', 'type')) {
                        $ticketPayload['type'] = $ticketData['name'] ?? null;
                    } else {
                        // fallback - set name
                        $ticketPayload['name'] = $ticketData['name'] ?? null;
                    }

                    // price / amount mapping
                    if (Schema::hasColumn('tickets', 'price')) {
                        $ticketPayload['price'] = $ticketData['price'] ?? 0;
                    } elseif (Schema::hasColumn('tickets', 'amount')) {
                        $ticketPayload['amount'] = $ticketData['price'] ?? 0;
                    } else {
                        $ticketPayload['price'] = $ticketData['price'] ?? 0;
                    }

                    // quantity_available / quantity mapping
                    if (Schema::hasColumn('tickets', 'quantity_available')) {
                        $ticketPayload['quantity_available'] = $ticketData['quantity'] ?? 0;
                    } elseif (Schema::hasColumn('tickets', 'quantity')) {
                        $ticketPayload['quantity'] = $ticketData['quantity'] ?? 0;
                    } else {
                        $ticketPayload['quantity_available'] = $ticketData['quantity'] ?? 0;
                    }

                    // Create the ticket associated to the event
                    $event->tickets()->create($ticketPayload);
                }

                return $event;
            });

            // Return created event with tickets
            return response()->json([
                'message' => 'Event created',
                'event' => $event->load('tickets'),
            ], 201);

        } catch (\Exception $e) {
            // Log for debugging and return 500
            Log::error('Event store error: ' . $e->getMessage(), [
                'payload' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to create event',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Optional: simple index/show methods for completeness
    public function index()
    {
        return response()->json(Event::with('tickets')->paginate(20));
    }

    public function show(Event $event)
    {
        return response()->json($event->load('tickets'));
    }
}
