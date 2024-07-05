<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'uid',
        'amount',
        'status',
        'user_id',
    ];

    public function order_details(){
        return $this->hasMany(OrderDetail::class);
    }

    public function order_details_not_deleted(){
        return $this->hasMany(OrderDetail::class)->where('deleted', 0);
    }
    public function transactions(){
        return $this->hasMany(Transaction::class);
    }
}
