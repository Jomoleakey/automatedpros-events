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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            // Foreign Key to link the Event to its Organizer (a User)
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->comment('Organizer ID');
            
            $table->string('title');
            $table->text('description');
            $table->dateTime('date');
            $table->string('location');
            $table->decimal('price', 8, 2)->default(0.00); // Base price for the event
            $table->integer('capacity');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};