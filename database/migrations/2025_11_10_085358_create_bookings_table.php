<?php

// database/migrations/*_create_bookings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            // Foreign Key to link the Booking to the Customer
            $table->foreignId('user_id')->constrained()->comment('Customer ID');
            // Foreign Key to link the Booking to the specific Ticket type
            $table->foreignId('ticket_id')->constrained();
            
            $table->integer('quantity');
            $table->decimal('total_price', 8, 2);
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};