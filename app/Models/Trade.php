<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trade extends Model
{
    use HasFactory;

    protected $fillable = [
        'enddate'
    ];

    public function order_detail(){
        return $this->hasOne(OrderDetail::class);
    }

    public function TradeWithoutEndDate(){
        return $this->hasMany(Trade::class)->where('enddate','1000-10-10 10:10:10');
    }
}
