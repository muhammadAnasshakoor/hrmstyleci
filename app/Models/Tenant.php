<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory;
    // Use SoftDeletes trait to enable soft deletes for the model
    use SoftDeletes;
    // Fillable attributes that can be mass assigned
    protected $fillable = [
        'user_id',
        'name',
        'phone_no',
        'website',
        'address',
        'logo_media_id',
        'document_media_id',
        'city',
        'state',
        'zip_code',
        'country',
        'status',
    ];

    //gives the defualt value of status as 1
    protected $attributes = [
        'status' => '1',
    ];

    // defining the relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function media()
    {
        return $this->belongsTo(Media::class);
    }

    public function companies()
    {
        return $this->hasMany(Company::class);
    }

    public function equipments()
    {
        return $this->hasMany(Equipment::class);
    }

    public function designations()
    {
        return $this->hasMany(Designation::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function policies()
    {
        return $this->hasMany(Policy::class);
    }

    public function duties()
    {
        return $this->hasMany(Duty::class);
    }

    public function employeeTransfers()
    {
        return $this->hasMany(EmployeeTransfer::class);
    }

    public function attendanceRosters()
    {
        return $this->hasMany(AttendanceRoster::class);
    }

    public function holidays()
    {
        return $this->hasMany(Holiday::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function attendanceReports()
    {
        return $this->hasMany(AttendanceReport::class);
    }

    public function subscriber()
    {
        return $this->hasOne(Subscriber::class);
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }
}
