<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralTrade extends Model
{
    use HasFactory;
    protected $fillable = ['order_id', 'person_id', 'trade_id', 'status'];

}
