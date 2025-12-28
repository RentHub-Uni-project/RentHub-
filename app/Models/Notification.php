<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';

    public $timestamps = false; 

    protected $fillable = [
        'user_id',
        'type',
        'reference_id',
        'title',
        'message',
        'created_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
