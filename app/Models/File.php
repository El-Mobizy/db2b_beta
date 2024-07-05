<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;
    protected $fillable = [
        'filename',
        'type',
        'location',
        'size',
        'referencecode',
        'temp',
        'deleted',
        'uid',
    ];
}
