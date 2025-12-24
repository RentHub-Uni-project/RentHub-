<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\UserRole;
use App\Enums\UserStatus;
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
        return $this->status == UserStatus::REJECTED->value;
    }
    public function isApproved()
    {
        return $this->status == UserStatus::APPROVED->value;
    }
    public function isPending()
    {
        return $this->status == UserStatus::PENDING->value;
    }

    public function isAdmin()
    {
        return $this->role == UserRole::ADMIN->value;
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
