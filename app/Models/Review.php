<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    /**
     * Table name (explicit because of non-standard naming)
     */
    protected $table = 'reviews';


    protected $fillable = [
        'booking_id',
        'appartment_id',
        'tenant_id',
        'rating',
        'comment',
    ];


    protected $casts = [
        'rating' => 'integer',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function appartment()
    {
        return $this->belongsTo(Appartment::class, 'appartment_id');
    }

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }
}


