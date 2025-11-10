<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // REQUIRED
use Illuminate\Database\Eloquent\Relations\HasOne;    // REQUIRED

class Booking extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id', // Customer ID
        'ticket_id',
        'quantity',
        'total_price',
        'status',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'total_price' => 'decimal:2',
    ];

    // --- ELOQUENT RELATIONSHIPS ---

    /**
     * Get the user (customer) who made the booking.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the ticket type that was booked.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the payment associated with this booking (1:1 relationship).
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
}