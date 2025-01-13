<?php

namespace App\Http\Controllers;

use App\Models\Person;
use Illuminate\Http\Request;
use  FedaPay\FedaPay;
use \FedaPay\Payout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class FedapayController extends Controller
{

    protected $person;
    public function __construct()
    {
        FedaPay::setApiKey(env('FEDAPAY_SECRET_KEY'));
        FedaPay::setEnvironment(env('FEDAPAY_ENVIRONMENT'));
        $this->person =Person::whereId(Auth::user()->id)->first() ;
    }

   


    public function process($amount,$number_phone, $country_code='bj')
    {
        try {

            $customer = [
                'firstname' => $this->person->first_name,
                'lastname' => $this->person->last_name,
                'email' =>Auth::user()->email,
                'phone_number' => [
                    'number'  => $number_phone,
                    'country' => $country_code
                ]
            ];
            // https://sandbox-api.fedapay.com
            // https://api.fedapay.com/v1/transactions/ID

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.env('FEDAPAY_SECRET_KEY'),
                'Content-Type' => 'application/json',
            ])->post('https://api.fedapay.com/v1/transactions', [
                'description' => 'Transaction for john.doe@example.com',
                'amount' =>$amount,
                'currency' => [
                    'iso' => 'XOF',
                ],
                'callback_url' => 'https://mywebsite.com/callback',
                'customer' => $customer,
            ]);
             // return redirect()->away($token->url);

            $transactionId = 'ID'; 
            $apiKey = 'VOTRE_CLE_API_SECRETE'; 

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post("https://sandbox-api.fedapay.com/v1/transactions/{$transactionId}/token");

            return $response->json();


            // return $response = \FedaPay\Customer::all();
            // return $this->fedapayTransactionData($amount,$number_phone, $country_code);
         
            // $transaction =  Payout::create(
            //     $this->fedapayTransactionData($amount,$number_phone, $country_code)
            // );



            // return $token;

            // return redirect()->away($token->url);
        } catch(\Exception $e) {
            return (new Service())->apiResponse(500,[], $e->getMessage());
        }
    }

    private function fedapayTransactionData($amount,$number_phone, $country_code='bj')
    {
        $customer_data = [
            'firstname' => $this->person->first_name,
            'lastname' => $this->person->last_name,
            'email' =>Auth::user()->email,
            'phone_number' => [
                'number'  => $number_phone,
                'country' => $country_code
            ]
        ];

        //  \FedaPay\Customer::create($customer_data);

        return [
            'description' => "wallet approvisionning",
            'amount' => $amount,
            'currency' => ['iso' => 'XOF'],
            'callback_url' => url('callback'),
            'mode' => 'mtn',
            'customer' => $customer_data
        ];
    }

    public function callback($id)
    {
        $transaction_id = $id;
        $message = '';

        try {
            $transaction = Payout::retrieve($transaction_id);
            switch($transaction->status) {
                case 'approved':
                    $message = 'Transaction approuvée.';
                break;
                case 'canceled':
                    $message = 'Transaction annulée.';
                break;
                case 'declined':
                    $message = 'Transaction déclinée.';
                break;
            }

            return $message;

        } catch(\Exception $e) {
            $message = $e->getMessage();
        }

    }

}

