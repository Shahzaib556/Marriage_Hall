<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'role',
        'name',        // user name
        'action',      // action type (Booking Created, Cancelled, etc.)
        'description', // detailed description
        'hall_name',   // ✅ new field
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
