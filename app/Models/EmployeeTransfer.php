<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeTransfer extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'from_company_id',
        'to_company_id',
        'from_duty_id',
        'to_duty_id',
        'started_at',
        'ended_at',
        'reason',
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
