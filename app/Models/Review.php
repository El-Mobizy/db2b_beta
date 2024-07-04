<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    public function file()
    {
        return $this->hasMany(File::class,'referencecode','filecode');
    }

    public function user(){
        return $this->belongsTo(User::class);
    }
    
    public function preorder_answer(){
        return $this->belongsTo(PreorderAnswers::class);
    }

}
