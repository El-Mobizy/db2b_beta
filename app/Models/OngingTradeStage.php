<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OngingTradeStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'complete'
    ];
}
