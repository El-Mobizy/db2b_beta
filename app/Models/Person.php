<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    use HasFactory;
    public function user(){
        return $this->belongsTo(User::class);
    }

    public function client(){
        return $this->hasOne(Client::class);
    }

    public function commission_wallets(){
        return $this->hasMany(CommissionWallet::class)->whereDeleted(0);
    }

}
