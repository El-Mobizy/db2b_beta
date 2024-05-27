<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreorderAnswers extends Model
{
    use HasFactory;
    public function preorder(){
        return $this->belongsTo(Preorder::class);
    }

    public function preorder_answer(){
        return $this->belongsTo(PreorderAnswers::class);
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

