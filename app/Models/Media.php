<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Media extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $fillable = [
        'user_id',
        'media_name',
        'media_path',
        'extension',
    ];

    public function tenant()
    {
        return $this->hasOne(Tenant::class);
    }

    public function company()
    {
        return $this->hasOne(Company::class);
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }
}
