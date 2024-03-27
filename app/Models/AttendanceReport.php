<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceReport extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'tenant_id',
        'company_id',
        'attendance_id',
        'employee_name',
        'checkin',
        'checkout',
        'date',
        'total_hours_worked',
        'type',
        'reason',
        'day',
        'expected_time',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
