<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'date',
        'location',
        'price',
        'capacity',
    ];

    /**
     * Get the organizer that created the event.
     */
    public function organizer(): BelongsTo
    {
        // Assuming your users model is App\Models\User
        // You might need to adjust the class name if it's different (e.g., App\Models\User)
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the tickets for the event.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}