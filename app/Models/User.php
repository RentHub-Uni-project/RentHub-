<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'passowrd' => "hashed"
    ];

    public function isRejected()
    {
        return $this->status == "rejected";
    }
    public function isApproved()
    {
        return $this->status == "approved";
    }
    public function isPending()
    {
        return $this->status == "pending";
    }

    public function serialize()
    {
        return [
            "id" => $this->id,
            "first_name" => $this->first_name,
            "last_name" => $this->last_name,
            "status" => $this->status,
            "phone" => $this->phone,
            "role" => $this->role,
            "birth_date" => $this->birth_date,
            "id_card" => $this->id_card,
            "avatar" => $this->avatar
        ];
    }
    public function resolveRouteBinding($value, $field = null)
    {
        $field = $field ?: $this->getRouteKeyName();

        $model = $this->where($field, $value)->first();

        if (!$model) {
            abort(response()->json([
                'message' => 'User not found',
            ], 404));
        }

        return $model;
    }
}
