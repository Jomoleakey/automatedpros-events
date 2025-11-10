<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'event_id',
        'type',
        'price',
        'quantity_available', 
    ];

    // --- ELOQUENT RELATIONSHIPS ---

    /**
     * Get the event that owns the ticket.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the bookings for this specific ticket type.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}