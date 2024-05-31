<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    public function preorders(){
        return $this->hasMany(Preorder::class);
    }

    public function ad(){
        return $this->hasMany(Ad::class);
    }
}

