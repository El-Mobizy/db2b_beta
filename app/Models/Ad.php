<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ad extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'statut',
        'file_code',
        'reject_reason',
        'validated_on',
        'deleted',
        'uid',
        'category_id',
        'owner_id',
        'location_id',
        'validated_by_id',
        'shop_id',
        'ad_code',
        'price',
        'final_price'
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

    public function user()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}




