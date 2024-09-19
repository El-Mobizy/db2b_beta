<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    use HasFactory;

    public function order(){
        return $this->belongsTo(Order::class);
    }

    public function trade(){
        return $this->hasOne(Trade::class);
    }

    public function ad()
{
    return $this->belongsTo(Ad::class);
}

}
