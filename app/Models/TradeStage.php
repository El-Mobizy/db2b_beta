<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'action_done_by'
    ];
}
