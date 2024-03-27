<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'tenant_id',
        'name',
        'phone_no',
        'gender',
        'emirates_id',
        'city',
        'state',
        'zip_code',
        'permanent_address',
        'local_address',
        'nationality',
        'designation_id',
        'profile_image_id',
        'passport_image_id',
        'emirates_image_id',
        'resume_image_id',
        'acount_title',
        'acount_no',
        'bank_name',
        'branch_name',
        'status',
    ];

    //gives the defualt value of status as 1
    protected $attributes = [
        'status' => '1',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function media()
    {
        return $this->belongsTo(Media::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function duties()
    {
        return $this->hasMany(Duty::class);
    }

    public function employeeTransfers()
    {
        return $this->hasMany(EmployeeTransfer::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function attendanceRosters()
    {
        return $this->hasMany(AttendanceRoster::class);
    }

    public function attendanceReport()
    {
        return $this->hasMany(AttendanceReport::class);
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }
}
