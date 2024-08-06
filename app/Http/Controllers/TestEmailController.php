<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;

class TestEmailController extends Controller
{
    public function verifyEmail($email)
    {
        $client = new Client();

        try {
            $response = $client->get('https://api.hunter.io/v2/email-verifier', [
                'query' => [
                    'email' => $email,
                    'api_key' => $this->hunterApiKey(),
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data['data']['result'] === 'deliverable') {
                return 'deliverable';
            } else {
                return 'undeliverable';
            }

        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to verify email'], 500);
        }
    }


    public function hunterApiKey(){
        return env('EMAIL_HUNTER_KEY');
    }
}
