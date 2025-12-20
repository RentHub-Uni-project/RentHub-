<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FavoriteAppartment extends Model
{
    protected $fillable = [
        'tenant_id',
        'appartment_id',
    ];

    public function appartment()
    {
        return $this->belongsTo(Appartment::class, 'appartment_id');
    }

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }
}

