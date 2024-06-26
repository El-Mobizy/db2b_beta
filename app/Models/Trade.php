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
        return $this->belongsTo(OrderDetail::class,'order_detail_id');
    }

    public function TradeWithoutEndDate(){
        return $this->hasMany(Trade::class)->where('enddate','1000-10-10 10:10:10');
    }

    public function onging_trade_stage(){
        return $this->hasMany(OngingTradeStage::class)->whereDeleted(0);
    }
}
