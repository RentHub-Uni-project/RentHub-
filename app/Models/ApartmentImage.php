<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApartmentImage extends Model
{
    protected $table = 'apartment_images';

    protected $fillable = [
        'apartment_id',
        'image_url',
        'is_main',
        'display_order',
    ];

    protected $casts = [
        'is_main' => 'boolean',
    ];

    public function apartment()
    {
        return $this->belongsTo(Apartment::class, 'apartment_id');
    }
}
