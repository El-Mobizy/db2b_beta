<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryAgentZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_agent_id', 'zone_id','deleted'
    ];
}
