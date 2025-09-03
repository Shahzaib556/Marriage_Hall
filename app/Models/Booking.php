<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'hall_id',
        'booking_date',
        'time_slot',
        'status',
        'guests' // 👉 add this if you want to save guest count
    ];

    // Each booking belongs to a user (the one who booked)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Each booking belongs to a hall
    public function hall()
    {
        return $this->belongsTo(Hall::class);
    }
}
