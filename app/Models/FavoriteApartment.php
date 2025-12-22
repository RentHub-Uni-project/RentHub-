<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FavoriteApartment extends Model
{
    protected $fillable = [
        'tenant_id',
        'apartment_id',
    ];

    public function apartment()
    {
        return $this->belongsTo(Apartment::class, 'apartment_id');
    }

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }
}
