<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceRoster extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $fillable = [

        'tenant_id',
        'employee_id',
        'batch_id',
        'date',
        'holiday',
        'check_in',
        'check_out',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
