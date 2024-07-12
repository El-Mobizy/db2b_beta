<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    protected $table = 'person';
    use HasFactory;
    protected $fillable = [
        'first_name',
        'last_name',
        'user_id',
        'country_id',
        'connected',
        'sex',
        'dateofbirth',
        'profile_img_code',
        'first_login',
        'phonenumber',
        'deleted',
        'uid',
        'type',
    ];
    public function user(){
        return $this->belongsTo(User::class);
    }

    public function client(){
        return $this->hasOne(Client::class);
    }

    public function admin(){
        return $this->hasOne(Admin::class);
    }

    public function commission_wallets(){
        return $this->hasMany(CommissionWallet::class)->whereDeleted(0);
    }

}
