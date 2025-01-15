<?php

namespace App\Http\Controllers;

use App\Models\Commission;
use App\Models\CommissionWallet;
use App\Models\Payement;
use App\Models\Person;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Http\Request;
use  FedaPay\FedaPay;
use Illuminate\Support\Facades\Auth;

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
        } catch (\UnexpectedValueException $e) {
            return response()->json(['error' => 'Invalid payload'.$e], 400);
        } catch (\FedaPay\Error\SignatureVerification $e) {
            return response()->json(['error' => 'Invalid signature'.$e], 400);
        }

        switch ($event->name) {

            case 'transaction.approved':
                $this->handleTransactionApproved($event);
                break;

            case 'transaction.canceled':
                $this->handleTransactionCanceled($event);
                break;

            case 'transaction.declined':
                $this-> handleTransactionDeclined($event);
                break;

            default:
                return response()->json(['error' => 'Unhandled event'], 400);
        }

        return response()->json(['message' => 'Event handled'], 200);
    }


    protected function handleTransactionApproved($data)
    {

        $payement = Payement::where('transaction_id', $data['entity']['id'])->first();

        if (!$payement) {
            return (new Service())->apiResponse(404,$data,"This payment does not exist.");
        }

        if ($payement->statut === 'approved') {
            return (new Service())->apiResponse(404,$data,"Transaction already approved successfully.");
        }

        $email = $data['entity']['customer']['email'];
        if (!$email) {
            throw new \Exception("Email is missing in the customer data.");
        }
        $amount = $data['entity']['amount'];
        if (!$amount) {
            throw new \Exception("Amount is missing in transaction data.");
        }

        if (!is_numeric($amount)) {
            throw new \Exception("Amount is not a valid number.");
        }

        if ($amount <= 0) {
            throw new \Exception("Amount must be greater than zero.");
        }

        $user = User::whereEmail($email)->first();
        if (!$user) {
            throw new \Exception("No user found with email: $email");
        }
        $userId = $user->id;
        $person = Person::whereUserId($userId)->first();
        if (!$person) {
            throw new \Exception("No person found with user ID: $userId");
        }
        $personId = $person->id;
        $commissionWallet = CommissionWallet::where('person_id', $personId)->first();
        if (!$commissionWallet) {
            throw new \Exception("No commission wallet found for person ID: $personId");
        }
        $credit = $amount + $commissionWallet->balance;

        (new WalletService())->updateUserWallet($personId,$credit);
        (new PayementController())->updatePayementStatus($data['entity']['id'],'approved');
    }

    protected function handleTransactionCanceled($data)
    {
        (new PayementController())->updatePayementStatus($data['entity']['id'],'canceled');
    }

    protected function handleTransactionDeclined($data)
    {
        (new PayementController())->updatePayementStatus($data['entity']['id'],'declined');
    }

}

