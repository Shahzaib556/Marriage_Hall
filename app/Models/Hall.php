<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hall extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id', 'name', 'location', 'capacity',
        'pricing', 'facilities', 'images', 'status'
    ];

    protected $casts = [
        'facilities' => 'array',
        'images' => 'array',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
