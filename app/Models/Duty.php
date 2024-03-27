<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Duty extends Model
{
    use SoftDeletes;
    use HasFactory;
    protected $fillable = [
        'user_id',
        'tenant_id',
        'employee_id',
        'company_id',
        'policy_id',
        'note',
        'joining_date',
        'status',
        'ended_at'
    ];


          //gives the defualt value of status as 1
          protected $attributes = [
            'status' => '1',
        ];


        
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function policy()
    {
        return $this->belongsTo(Policy::class);
    }
    public function equipments()
    {
        return $this->belongsToMany(Equipment::class, 'duty_equipment');
    }

}
