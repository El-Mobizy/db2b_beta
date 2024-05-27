<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Preorder extends Model
{
    use HasFactory;

    public function country(){
        return $this->belongsTo(Country::class);
    }

    public function category(){
        return $this->belongsTo(Category::class);
    }

    public function typeoftype(){
        return $this->belongsTo(TypeOfType::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function preorder_answers(){
        return $this->hasMany(PreorderAnswers::class);
    }

    public function file()
    {
        return $this->hasMany(File::class,'referencecode','filecode');
    }
}


