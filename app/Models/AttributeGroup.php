<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttributeGroup extends Model
{
    use HasFactory;
    protected $fillable = [
        'attribute_id',
        'group_title_id',
        'deleted',
        'uid',
    ];
}
