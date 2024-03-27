<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Equipment extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $fillable = [
        'title',
        'user_id',
        'tenant_id',
        'status'
    ];

    //gives the defualt value of status as 1
    protected $attributes = [
        'status' => '1',
    ];
    
    protected $table = 'equipments';

    public function duties()
    {
        return $this->belongsToMany(Duty::class, 'duty_equipment');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
