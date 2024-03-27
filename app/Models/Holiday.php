<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Holiday extends Model
{
    use SoftDeletes;
    use HasFactory;
    protected $fillable = [
        'company_id',
        'tenant_id',
        'name',
        'starting_date',
        'ending_date',
        'status',
    ];

    //gives the defualt value of status as 1
    protected $attributes = [
        'status' => '1',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
