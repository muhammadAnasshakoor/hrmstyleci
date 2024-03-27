<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use SoftDeletes;
    use HasFactory;
    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'branch',
        'address',
        'phone_no',
        'country',
        'city',
        'state',
        'zip_code',
        'registration_no',
        'logo_media_id',
        'document_media_id',
        'note',
        'status'
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
    public function holidays()
    {
        return $this->hasMany(Holiday::class);
    }

    public function duties()
    {
        return $this->hasMany(Duty::class);
    }
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}
