<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Response;
use App\Models\Event;
use App\Models\Ticket;

class EventController extends Controller
{
    /**
     * Display a paginated list of events (with tickets).
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 15);
        return response()->json(Event::with('tickets')->paginate($perPage));
    }

    /**
     * Display a single event (with tickets).
     */
    public function show(Event $event)
    {
        return response()->json($event->load('tickets'));
    }

    /**
     * Store a newly created event and its tickets.
     */
    public function store(Request $request)
    {
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

        $user = $request->user() ?? auth()->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated. Provide a valid token.'], 401);
        }

        try {
            $event = DB::transaction(function () use ($fields, $user) {
                $event = Event::create([
                    'user_id' => $user->id,
                    'title' => $fields['title'],
                    'description' => $fields['description'] ?? null,
                    'date' => $fields['date'],
                    'location' => $fields['location'] ?? null,
                    'price' => $fields['price'] ?? null,
                    'capacity' => $fields['capacity'] ?? null,
                ]);

                $tickets = $fields['tickets'] ?? [];
                foreach ($tickets as $ticketData) {
                    $ticketPayload = $this->mapTicketPayload($ticketData);
                    $event->tickets()->create($ticketPayload);
                }

                return $event;
            });

            return response()->json([
                'message' => 'Event created',
                'event' => $event->load('tickets'),
            ], 201);

        } catch (\Exception $e) {
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

    /**
     * Update the specified event.
     * Only the event owner may update.
     * If tickets are provided we will replace existing tickets with the provided set.
     */
    public function update(Request $request, Event $event)
    {
        $user = $request->user() ?? auth()->user();
        if (! $user || $user->id !== $event->user_id) {
            return response()->json(['message' => 'Forbidden. Only owner may update.'], 403);
        }

        $fields = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'sometimes|required|date',
            'location' => 'nullable|string|max:255',
            'price' => 'nullable|numeric',
            'capacity' => 'nullable|integer',
            'tickets' => 'sometimes|array',
            'tickets.*.name' => 'required_with:tickets|string|max:255',
            'tickets.*.price' => 'required_with:tickets|numeric',
            'tickets.*.quantity' => 'required_with:tickets|integer|min:0',
        ]);

        try {
            $updated = DB::transaction(function () use ($event, $fields) {
                // Update event attributes (only provided keys)
                $updatable = array_filter([
                    'title' => $fields['title'] ?? null,
                    'description' => array_key_exists('description', $fields) ? $fields['description'] : null,
                    'date' => $fields['date'] ?? null,
                    'location' => $fields['location'] ?? null,
                    'price' => $fields['price'] ?? null,
                    'capacity' => $fields['capacity'] ?? null,
                ], function ($v) {
                    return $v !== null;
                });

                if (!empty($updatable)) {
                    $event->update($updatable);
                }

                // If tickets payload provided, replace existing tickets
                if (array_key_exists('tickets', $fields)) {
                    // Delete existing tickets (adjust if you want to preserve and patch instead)
                    $event->tickets()->delete();

                    foreach ($fields['tickets'] as $ticketData) {
                        $ticketPayload = $this->mapTicketPayload($ticketData);
                        $event->tickets()->create($ticketPayload);
                    }
                }

                return $event->fresh()->load('tickets');
            });

            return response()->json([
                'message' => 'Event updated',
                'event' => $updated,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Event update error: ' . $e->getMessage(), [
                'payload' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to update event',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified event.
     * Only owner may delete.
     */
    public function destroy(Request $request, Event $event)
    {
        $user = $request->user() ?? auth()->user();
        if (! $user || $user->id !== $event->user_id) {
            return response()->json(['message' => 'Forbidden. Only owner may delete.'], 403);
        }

        try {
            DB::transaction(function () use ($event) {
                // delete related tickets first if no cascade
                if ($event->tickets()->exists()) {
                    $event->tickets()->delete();
                }
                $event->delete();
            });

            return response()->json(['message' => 'Event deleted'], 200);
        } catch (\Exception $e) {
            Log::error('Event delete error: ' . $e->getMessage(), [
                'event_id' => $event->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['message' => 'Failed to delete event', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Placeholder for booking endpoint.
     * Prefer implementing booking creation in BookingController.
     */
    public function book(Request $request, Event $event)
    {
        return response()->json([
            'message' => 'Use BookingController@store to create bookings. This endpoint is a placeholder.'
        ], Response::HTTP_NOT_IMPLEMENTED);
    }

    /**
     * Map incoming ticket payload to actual DB column names defensively.
     *
     * @param array $ticketData
     * @return array
     */
    protected function mapTicketPayload(array $ticketData): array
    {
        $payload = [];

        // --- NAME/TYPE FIX (to solve the first error: tickets.name) ---
        $ticketName = $ticketData['name'] ?? null; 
        if (Schema::hasColumn('tickets', 'type') && !Schema::hasColumn('tickets', 'name')) {
            $payload['type'] = $ticketName;
        } else {
            // Default to 'name' as the error shows it is the required column.
            $payload['name'] = $ticketName;
        }
        
        // price / amount
        if (Schema::hasColumn('tickets', 'price')) {
            $payload['price'] = $ticketData['price'] ?? 0;
        } elseif (Schema::hasColumn('tickets', 'amount')) {
            $payload['amount'] = $ticketData['price'] ?? 0;
        } else {
            $payload['price'] = $ticketData['price'] ?? 0;
        }

        // --- QUANTITY FIX (to solve the second error: tickets.quantity_total) ---
        $ticketQuantity = $ticketData['quantity'] ?? 0;
        
        if (Schema::hasColumn('tickets', 'quantity_total')) {
            // Map input 'quantity' to the required 'quantity_total' column
            $payload['quantity_total'] = $ticketQuantity;
            
            // Also map to 'quantity_available' if it exists, as it should equal total upon creation.
            if (Schema::hasColumn('tickets', 'quantity_available')) {
                $payload['quantity_available'] = $ticketQuantity;
            }
            
        } elseif (Schema::hasColumn('tickets', 'quantity_available')) {
            // Fallback to the original logic
            $payload['quantity_available'] = $ticketQuantity;
        } elseif (Schema::hasColumn('tickets', 'quantity')) {
            // Fallback to the original logic
            $payload['quantity'] = $ticketQuantity;
        } else {
            // Ultimate fallback
            $payload['quantity_available'] = $ticketQuantity;
        }

        return $payload;
    }
}
