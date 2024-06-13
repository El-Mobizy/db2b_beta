<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ad extends Model
{
    use HasFactory;

    protected $fillable = [
        'statut'
    ];

    public function ad_detail()
    {
        return $this->hasMany(AdDetail::class);
    }

    public function file()
    {
        return $this->hasMany(File::class,'referencecode','file_code');
    }

    public function category(){
        return $this->belongsTo(Category::class);
    }

    public function shop(){
        return $this->belongsTo(Shop::class);
    }
}
