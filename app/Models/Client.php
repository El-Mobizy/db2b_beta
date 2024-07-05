<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'person_id',
        'is_merchant',
        'is_deliverer',
        'uid',
        'deleted',
    ];


    public function person(){
        return $this->belongsTo(Person::class);
    }

}
