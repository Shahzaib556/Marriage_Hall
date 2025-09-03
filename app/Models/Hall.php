<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hall extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'name',
        'location',
        'capacity',
        'pricing',
        'facilities',
        'images',
        'status'
    ];

    protected $casts = [
        'facilities' => 'array',
        'images' => 'array',
    ];

    // Each hall belongs to an owner (User with role = hall_owner)
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    // A hall can have many bookings
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
