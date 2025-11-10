<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // REQUIRED for relationship

class Payment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'booking_id',
        'amount',
        'method',
        'status',
        'transaction_id',
    ];

    // --- ELOQUENT RELATIONSHIPS ---

    /**
     * Get the booking associated with the payment (1:1 relationship).
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}