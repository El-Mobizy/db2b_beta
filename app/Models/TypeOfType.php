<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeOfType extends Model
{
    use HasFactory;

    public function preorders(){
        return $this->hasMany(Preorder::class);
    }
}

