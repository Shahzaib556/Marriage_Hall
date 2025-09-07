<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'bank_name',        // ✅ Added for hall owners
        'account_number',   // ✅ Added for hall owners
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /* ---------------- ROLE HELPERS ---------------- */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isHallOwner()
    {
        return $this->role === 'hall_owner'; // ✅ Corrected
    }

    public function isUser()
    {
        return $this->role === 'user';
    }

    /* ---------------- RELATIONSHIPS ---------------- */

    // A user can make many bookings
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    // A hall owner can own many halls
    public function halls()
    {
        return $this->hasMany(Hall::class, 'owner_id');
    }
}
