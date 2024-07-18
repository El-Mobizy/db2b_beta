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

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function deliveryAgents()
    {
        return $this->hasMany(DeliveryAgency::class, 'delivery_agent_zone');
    }

}
