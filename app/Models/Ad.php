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
}
