<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'total_ads',
        'parent_id',
        'link_category_id',
        'attribute_group_id',
        'deleted',
        'is_top',
        'filecode',
        'uid',
    ];

    public function preorders(){
        return $this->hasMany(Preorder::class);
    }

    public function ad(){
        return $this->hasMany(Ad::class);
    }

    public function file()
    {
        return $this->hasMany(File::class,'referencecode','filecode');
    }
}

