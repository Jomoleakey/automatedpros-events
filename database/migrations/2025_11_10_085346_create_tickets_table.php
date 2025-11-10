<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            // Links the ticket type to a specific event, with cascade delete
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->string('name');
            // Stores the price with 8 digits in total, 2 of which are after the decimal point
            $table->decimal('price', 8, 2); 
            // Total quantity of this ticket type originally created
            $table->integer('quantity_total');
            // Quantity currently available for purchase
            $table->integer('quantity_available'); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};