Event Ticketing System (Laravel API) - Tester Guide

This document provides the full instructions for setting up the environment, running the necessary servers, and executing the core event creation and booking verification test.

1. Full Setup & Database Reset

Run this command to drop all tables, run all migrations, and re-seed the database with fresh test data.

php artisan migrate:fresh --seed


2. Running the Servers (MANDATORY)

The application requires two separate processes running in the terminal. The second process (queue:work) is critical for processing payments and sending confirmations.

Terminal 1: Web Server (API)

php artisan serve

Runs the main web application and API endpoints.

Terminal 2: Queue Worker

php artisan queue:work

Processes background jobs, including payment confirmations. Keep this window open!

3. Verification Test Flow

Use the seeded test users and cURL commands below to run a full event creation and booking cycle. You must replace the placeholder variables with the real values from the responses.

Step 3.1: Login to Get Tokens

We need the Admin Token to create the event and the Customer Token to make the booking.

A. Login as Admin (admin1@example.com / password)

# Store the token from the response.
curl -X POST "http://localhost/api/login" \
-H "Accept: application/json" -H "Content-Type: application/json" \
-d '{"email": "admin1@example.com","password": "password"}'


B. Login as Customer (customer@example.com / password)

# Store the token from the response. This is YOUR_CUSTOMER_TOKEN.
curl -X POST "http://localhost/api/login" \
-H "Accept: application/json" -H "Content-Type: application/json" \
-d '{"email": "customer@example.com","password": "password"}'


Step 3.2: Create an Event

Use the Admin Token to create a new event.

ACTION: Replace YOUR_ADMIN_TOKEN with the token obtained in Step 3.1A.

KEY RESULT: Note the tickets[0].id from the response. This is your TICKET_ID.

<!-- end list -->

curl -X POST "http://localhost/api/events" \
-H "Accept: application/json" \
-H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
-H "Content-Type: application/json" \
-d '{
    "title": "Fix Check Success Test Event",
    "description": "API integrity check event.",
    "date": "2026-01-01 10:00:00",
    "location": "Virtual",
    "price": 50.00,
    "capacity": 5,
    "tickets": [
        {
            "name": "General Admission",
            "price": 50.00,
            "quantity": 5
        }
    ]
}'


Step 3.3: Create a Pending Booking

Use the Customer Token to reserve 2 tickets.

ACTION: Replace YOUR_CUSTOMER_TOKEN and {TICKET_ID}.

KEY RESULT: Note the new booking.id from the response. This is your BOOKING_ID.

<!-- end list -->

curl -X POST "http://localhost/api/bookings" \
-H "Accept: application/json" \
-H "Authorization: Bearer YOUR_CUSTOMER_TOKEN" \
-H "Content-Type: application/json" \
-d '{
    "ticket_id": {TICKET_ID},
    "quantity": 2
}'


Step 3.4: Process Payment and Verify Queue Worker

This is the final, critical step that tests the payment transaction and the queue worker.

ACTION: Replace YOUR_CUSTOMER_TOKEN and {BOOKING_ID}.

Response Check: Verify the API response shows the booking status as "paid".

Queue Check (CRITICAL!): Immediately check the terminal running php artisan queue:work. You should see the logs confirming the job processed successfully:

<!-- end list -->

[2025-11-10 18:00:00] [1] Processing booking confirmation for Booking ID: {BOOKING_ID}
[2025-11-10 18:00:00] [1] Sending confirmation to Test Customer for 2 x General Admission to attend Fix Check Success Test Event on 2026-01-01 10:00:00.
[2025-11-10 18:00:00] [1] Booking ID {BOOKING_ID} confirmation job finished.


curl -X POST "http://localhost/api/bookings/{BOOKING_ID}/pay" \
-H "Accept: application/json" \
-H "Authorization: Bearer YOUR_CUSTOMER_TOKEN" \
-H "Content-Type: application/json" \
-d '{"method": "Queue Test Success"}'
