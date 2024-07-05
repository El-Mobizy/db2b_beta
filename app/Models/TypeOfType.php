<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeOfType extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'libelle',
        'codereference',
        'deleted',
        'uid',
    ];

    public function preorders(){
        return $this->hasMany(Preorder::class);
    }
}

