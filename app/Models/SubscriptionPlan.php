<?php
namespace App\Models;

use App\Http\Controllers\API\SubscriberController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'price',
        'discounted_price',
        'description',
        'status',
    ];

     //gives the defualt value of status as 1
     protected $attributes = [
        'status' => '1',
    ];

    public function subscribers()
    {
        return $this->hasMany(Subscriber::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }
}
