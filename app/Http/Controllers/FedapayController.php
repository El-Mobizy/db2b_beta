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

    public function processPackage($amount,$number_phone, $country_code='bj',$mode='mtn_open'){
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


            $transaction = \FedaPay\Transaction::create(
                [
                    'description' => 'Transaction for john.doe@example.com',
                    'amount' =>$amount,
                    'currency' => [
                        'iso' => 'XOF',
                    ],
                    'callback_url' => 'https://mywebsite.com/callback',
                    'customer' => $customer,
                ]);

                $token = $transaction->generateToken()->token;

                $result = $transaction->sendNowWithToken($mode,$token);

                return $result;

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

