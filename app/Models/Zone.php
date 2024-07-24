<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    use HasFactory;
    protected $fillable = [
        'uid', 'city_name', 'latitude', 'longitude', 'country_id', 'active', 'deleted'
    ];

    public function delivery_agency()
    {
        return $this->belongsTo(DeliveryAgency::class);
    }

    public function deliver_agent_zone(){
        return $this->hasOne(DeliveryAgentZone::class);
    }

}
