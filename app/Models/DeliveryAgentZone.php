<?php

namespace App\Models;

use DateTimeZone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryAgentZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_agent_id', 'zone_id','deleted'
    ];

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }
}
