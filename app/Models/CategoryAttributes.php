<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryAttributes extends Model
{
    use HasFactory;
    protected $fillable = [
        'fieldtype',
        'label',
        'possible_value',
        'isrequired',
        'description',
        'order_no',
        'is_price_field',
        'is_crypto_price_field',
        'search_criteria',
        'is_active',
        'deleted',
        'uid',
    ];
}
