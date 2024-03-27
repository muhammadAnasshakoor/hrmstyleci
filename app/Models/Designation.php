<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Designation extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        'user_id',
        'tenant_id',
        'title',
        'status'
    ];

    //gives the defualt value of status as 1
    protected $attributes = [
        'status' => '1',
    ];


    public function employees()
    {
        return $this->hasMany(Employee::class);
    }



    public function duties()
    {
        return $this->hasMany(Duty::class);
    }
}
