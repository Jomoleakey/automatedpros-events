<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // REQUIRED for API
use Illuminate\Database\Eloquent\Relations\HasMany; // REQUIRED for relationships

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone', // ADDED: Matches migration
        'role',  // ADDED: Matches migration
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // --- ELOQUENT RELATIONSHIPS ---

    /**
     * Get the events organized by this user (if they are an 'organizer').
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Get the bookings made by this user (if they are a 'customer').
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}