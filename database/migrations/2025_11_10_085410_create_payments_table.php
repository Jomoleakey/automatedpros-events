<?php

// database/migrations/*_create_payments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            // Foreign Key to link the Payment back to its Booking (1:1 relationship)
            $table->foreignId('booking_id')->constrained()->unique()->onDelete('cascade');
            
            $table->decimal('amount', 8, 2);
            $table->string('method'); // e.g., 'credit card', 'M-Pesa', 'PayPal'
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->string('transaction_id')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};