<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\Booking;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ----------------------------------------------------
        // CRITICAL FIX: TRUNCATE TABLES TO AVOID UNIQUE ERRORS
        // ----------------------------------------------------
        Booking::truncate();
        Ticket::truncate();
        Event::truncate();
        User::truncate(); // Truncate last to clear all existing users

        // ----------------------------------------------------
        // 1. Create Users: 2 Admins, 3 Organizers, 10 Customers (Total 15 Users)
        // ----------------------------------------------------

        // 1.1 Admins (2 required)
        User::factory()->create([
            'name' => 'Admin 1',
            'email' => 'admin1@example.com',
            'role' => 'admin',
            'password' => Hash::make('password')
        ]);
        $admins = User::factory(1)->create(['role' => 'admin']);


        // 1.2 Organizers (3 required) - Explicitly defined test account
        $test_organizer = User::factory()->create([
            'name' => 'Organizer 1',
            'email' => 'organizer1@example.com',
            'role' => 'organizer',
            'password' => Hash::make('password')
        ]);
        $organizers = collect([$test_organizer]);
        // Create 2 more organizers
        $organizers = $organizers->merge(User::factory(2)->create(['role' => 'organizer']));


        // 1.3 Customers (10 required) - Explicitly defined test account
        $test_customer = User::factory()->create([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'role' => 'customer',
            'password' => Hash::make('password')
        ]);
        $customers = collect([$test_customer]);
        // Create 9 more customers (for a total of 10)
        $customers = $customers->merge(User::factory(9)->create(['role' => 'customer']));
        
        // ----------------------------------------------------
        // 2. Create Events (5 Events) and Tickets (15 Tickets)
        // ----------------------------------------------------
        
        $ticketTypes = ['Standard', 'VIP', 'Early Bird'];
        
        for ($i = 0; $i < 5; $i++) {
            // Assign event to a random organizer
            $organizer = $organizers->random();
            
            $event = Event::factory()->create([
                'user_id' => $organizer->id,
                'title' => 'Event ' . ($i + 1) . ' - ' . Str::random(5),
                // --- CRITICAL FIXES ADDED HERE ---
                'description' => fake()->paragraph(2),
                'date' => fake()->dateTimeBetween('+1 week', '+1 year')->format('Y-m-d'),
                'location' => fake()->city(),
                // ----------------------------------
                'capacity' => 500,
            ]);

            // Create 3 Tickets per Event (Total 5 * 3 = 15 Tickets)
            foreach ($ticketTypes as $type) {
                Ticket::create([
                    'event_id' => $event->id,
                    'name' => $type . ' Pass',
                    'price' => $type === 'VIP' ? 150.00 : 50.00,
                    'quantity_total' => $type === 'VIP' ? 100 : 200,
                    'quantity_available' => $type === 'VIP' ? 100 : 200,
                ]);
            }
        }

        $tickets = Ticket::all();

        // ----------------------------------------------------
        // 3. Create Bookings (20 Bookings)
        // ----------------------------------------------------

        for ($i = 0; $i < 20; $i++) {
            $customer = $customers->random();
            $ticket = $tickets->random();
            $quantity = 1; 

            if ($ticket->quantity_available >= $quantity) {
                $booking = Booking::create([
                    'user_id' => $customer->id,
                    'ticket_id' => $ticket->id,
                    'quantity' => $quantity,
                    'total_price' => $ticket->price * $quantity,
                    // Set 50% confirmed, 50% pending for realism
                    'status' => fake()->randomElement(['confirmed', 'pending']), 
                ]);

                if ($booking->status !== 'cancelled') {
                    $ticket->decrement('quantity_available', $quantity);
                }
            }
        }
    }
}