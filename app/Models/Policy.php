<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Policy extends Model
{
    use SoftDeletes;
    use HasFactory;
    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'shift_start',
        'shift_end',
        'late_allow',
        'early_departure_allow',
        'late_deduction',
        'early_deduction',
        'status',
    ];

    //gives the defualt value of status as 1
    protected $attributes = [
        'status' => '1',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function duties()
    {
        return $this->hasMany(Duty::class);
    }
}
