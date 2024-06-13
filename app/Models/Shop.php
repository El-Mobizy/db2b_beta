<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    use HasFactory;
    public function files()
    {
        return $this->hasMany(File::class,'referencecode','filecode');
    }

    public function ads()
    {
        return $this->hasMany(Ad::class);
    }
}
