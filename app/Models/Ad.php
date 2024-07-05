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
}

// "id": 23,
//             "title": "Productsjk",
//             "statut": 6,
//             "file_code": "1WSNabR",
//             "reject_reason": null,
//             "validated_on": null,
//             "deleted": false,
//             "uid": "e574345a-281c-11ef-a184-00ff5210c7f1",
//             "category_id": 1,
//             "owner_id": 2,
//             "location_id": 1,
//             "validated_by_id": null,
//             "shop_id": 1,
//             "ad_code": "s9DtyB8",
//             "created_at": "2024-06-11T18:03:24.000000Z",
//             "updated_at": "2024-06-11T18:03:24.000000Z",
//             "price": "10",
//             "final_price": null,
//             "category_title": "Voiture",
//             "ad_detail": [],
//             "file": [],
