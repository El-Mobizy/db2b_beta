<?php

namespace App\Http\Controllers;

use App\Models\Commission;
use App\Models\CommissionWallet;
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
    }

    public function processPackage($person,$amount,$number_phone, $country_code='bj',$mode='mtn_open'){
        try {

            $customer = [
                'firstname' => $person->first_name,
                'lastname' => $person->last_name,
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
                    // 'callback_url' => 'https://mywebsite.com/callback',
                    'customer' => $customer,
                ]);

                $token = $transaction->generateToken()->token;

                $result = $transaction->sendNowWithToken($mode,$token);

                return $result;

        } catch(\Exception $e) {
            return (new Service())->apiResponse(500,[], $e->getMessage());
        }
    }

    public function handleFedapayPackageWebhook(Request $request)
    {
        $endpointSecret = env('FEDAPAY_WEBHOOK_SECRET');
        $payload = $request->getContent();
        $sigHeader = $request->header('x_fedapay_signature');
        $event = null;

        try {
            $event = \FedaPay\Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
            (new MailController())->sendNotification(Auth::user()->id,$event ,$event ,2);
        } catch (\UnexpectedValueException $e) {
            return response()->json(['error' => 'Invalid payload'.$e], 400);
        } catch (\FedaPay\Error\SignatureVerification $e) {
            return response()->json(['error' => 'Invalid signature'.$e], 400);
        }

        $eventData = $event->data['object'];

        switch ($event->name) {
            case 'transaction.created':
                $this->handleTransactionCreated($eventData);
                break;

            case 'transaction.approved':
                $this->handleTransactionApproved($eventData);
                break;

            case 'transaction.canceled':
                $this->handleTransactionCanceled($eventData);
                break;

            default:
                return response()->json(['error' => 'Unhandled event'], 400);
        }

        return response()->json(['message' => 'Event handled'], 200);
    }

    protected function handleTransactionCreated($data)
    {
        (new PayementController())->storePayement(
            $data['customer']['email'],
            $data['id'],
            $data['currency']['code'],
            $data['amount'],
            $data['status'],
            $data['description'],
        );
    }

    protected function handleTransactionApproved($data)
    {
        (new PayementController())->updatePayementStatus($data['id'],'approved');
    }

    protected function handleTransactionCanceled($data)
    {
        (new PayementController())->updatePayementStatus($data['id'],'canceled');
    }

    protected function handleTransactionDeclined($data)
    {
        (new PayementController())->updatePayementStatus($data['id'],'declined');
    }

}

