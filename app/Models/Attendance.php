<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'tenant_id',
        'company_id',
        'date',
        'check_in',
        'check_out',
        'check_in_location',
        'check_out_location',
        'total_hours',
        'type',
        'reason'
    ];
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
    public function attendanceReport()
    {
        return $this->hasOne(AttendanceReport::class);
    }
}
