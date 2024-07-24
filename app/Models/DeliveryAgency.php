<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryAgency extends Model
{
    use HasFactory;
    protected $fillable = [
        'ip_address',
        'email',
        'uid',
    ];

    public function person(){
        return $this->belongsTo(Person::class);
    }

    public function zone(){
        return $this->hasOne(Zone::class);
    }
}
