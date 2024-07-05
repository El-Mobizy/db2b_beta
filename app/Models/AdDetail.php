<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdDetail extends Model
{
    use HasFactory;
    protected $fillable = [
        'ad_id',
        'fieldtype',
        'label',
        'value_entered',
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
