<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class Service extends Controller
{
    public function generateRandomAlphaNumeric($length) {
        $bytes = random_bytes(ceil($length * 3 / 4));
        return substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $length);
    }
}
