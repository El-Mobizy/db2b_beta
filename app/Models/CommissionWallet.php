<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommissionWallet extends Model
{
    use HasFactory;
    protected $fillable = [
        'balance'
    ];
    public function person(){
        return $this->belongsTo(Person::class);
    }

    public function payements()
    {
        return $this->hasMany(Payement::class, 'commission_wallet_id');
    }

}
