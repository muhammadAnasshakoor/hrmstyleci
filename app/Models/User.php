<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use HasRoles;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'status',
        'email',
        'password',
        'modified_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];
    // thses are the relations b/w the user and  columns of other models

    public function tenant()
    {
        return $this->hasOne(Tenant::class);
    }

    public function company()
    {
        return $this->hasOne(Company::class);
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function policy()
    {
        return $this->hasOne(Policy::class);
    }

    public function duties()
    {
        return $this->hasMany(Duty::class);
    }

    public function subscribers()
    {
        return $this->hasMany(Subscriber::class);
    }

    public function subscriptionPlans()
    {
        return $this->hasMany(SubscriptionPlan::class);
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    protected $attributes = [
        'status' => '1',
    ];
}
