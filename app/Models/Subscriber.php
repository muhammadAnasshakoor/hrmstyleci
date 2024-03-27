<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscriber extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'subscription_plan_id',
        'user_id',
        'start_date',
        'end_date',
        'amount',
        'status',
    ];

     //gives the defualt value of status as 1
     protected $attributes = [
        'status' => '1',
    ];
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscriptionPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
