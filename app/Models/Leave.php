<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Leave extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'user_id',
        'start_date',
        'end_date',
        'total_days',
        'description',
        'status',
    ];

//gives the defualt value of status as 1
protected $attributes = [
    'status' => 'pending'
];


public function tenant()
{
    return $this->belongsTo(Tenant::class);
}

public function employee()
{
    return $this->belongsTo(Employee::class);
}
public function user()
{
    return $this->belongsTo(User::class);
}


}
