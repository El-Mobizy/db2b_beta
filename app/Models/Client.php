<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'is_merchant'
    ];

    public function person(){
        return $this->belongsTo(Person::class);
    }

}
