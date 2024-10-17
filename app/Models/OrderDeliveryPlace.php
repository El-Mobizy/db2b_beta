<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDeliveryPlace extends Model
{
    use HasFactory;

    public function order()
{
    return $this->belongsTo(Order::class);
}

public function address()
{
    return $this->belongsTo(Address::class);
}

}
