<?php

/**
 * @file plugins/paymethod/malipo/Utilities.php
 *
 * Copyright (c) 2024 OtCloud Company Limited
 * Copyright (c) 2024 Otuoma Sanya
 * Distributed under the GNU GPL v3.
 * @class Utilities
 * @brief Mpesa/Stripe payment page
 */

namespace APP\plugins\paymethod\malipo;

use APP\core\Application;
use Error;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use PKP\payment\QueuedPayment;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;

//require_once 'vendor/autoload.php';
require_once('vendor/stripe/init.php');

class Utilities {

    public MalipoPlugin $plugin;
    /**
     * @var mixed|\PKP\cache\generic_cache_miss|null
     */
    private string $stripeSecretKey;
    private \Stripe\StripeClient $stripeClient;

    public function __construct(MalipoPlugin $plugin){
        $this->plugin = $plugin;

        $this->stripeSecretKey = $plugin->getSetting($this->plugin->getCurrentContextId(), 'stripeSecretKey');
        $this->stripeClient = new \Stripe\StripeClient(["api_key"=>$this->stripeSecretKey]);
    }

    public function initPaymentSession(QueuedPayment $queuedPayment): Session {

        $callbackUrl  = $this->plugin->getRequest()->url(null, 'payment', 'plugin', [$this->plugin->getName(), 'stripe-callback', $queuedPayment->getId()]);
        $callbackUrl .= "?checkout_session_id={CHECKOUT_SESSION_ID}";
        $paymentManager = Application::getPaymentManager($this->plugin->request->getContext());

        $stripeSession = $this->stripeClient->checkout->sessions->create([
            'return_url' => $callbackUrl,
            'line_items' => [
                [
                    'quantity'   => 1,
                    'price_data' => [
                        'currency'     => $queuedPayment->getCurrencyCode(),
                        'unit_amount'  => $queuedPayment->getAmount() * 100,
                        'product_data' => [
                            'name' => $paymentManager->getPaymentName($queuedPayment),
                        ]
                    ]

                ],
            ],
            'mode' => 'payment',
            'ui_mode' => 'embedded',
        ]);

        return  $stripeSession;
    }

    /**
     * @throws ApiErrorException
     */
    public function retrieveSessionStatus($request): Session {
        try {
            // retrieve JSON from POST body
            $jsonStr = @file_get_contents('php://input');

            $session_id = $request->getUserVar('checkout_session_id');

            return $this->stripeClient->checkout->sessions->retrieve($session_id);

        } catch (Error $e) {
            error_log($e->getMessage());
            throw new Exception($e->getMessage());
        }
    }

    public function STKPush($BusinessShortCode, $Amount, $PartyA, $PartyB, $PhoneNumber, $CallBackURL, $AccountReference, $TransactionDesc): bool|string
    {

        $context = Application::get()->getRequest()->getContext();

        $testMode = $this->plugin->isTestMode($context, 'mpesa');

        if( $testMode ){
            $reqUrl = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
            $token = $this->generateSandBoxToken();
        }else{
            $reqUrl = 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
            $token = $this->generateLiveToken();
        }

        $timestamp = '20' . date(    "ymdhis");
        $passKey = $this->plugin->getSetting($context->getId(), "mpesaPassKey");
        $password= base64_encode($BusinessShortCode.$passKey.$timestamp);
        $transactionType = "CustomerPayBillOnline";

        $reqHeaders = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token
        ];

        $reqBody = [
            'BusinessShortCode' => $BusinessShortCode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => $transactionType,
            'Amount' => $Amount,
            'PartyA' => $PartyA,
            'PartyB' => $PartyB,
            'PhoneNumber' => $PhoneNumber,
            'CallBackURL' => $CallBackURL,
            'AccountReference' => $AccountReference,
            'TransactionDesc' => $TransactionDesc,
        ];

        $httpClient = Application::get()->getHttpClient();

        try {
            $resp = $httpClient->request('POST', $reqUrl, [
                'headers' => $reqHeaders,
                'json' => $reqBody
            ]);
        } catch (GuzzleException $e) {
            error_log("====STK PUSH FAILED======");
            die("STKPush Failed");
        }
        error_log("======STK PUSH COMPLETED=======");

        return $resp->getBody();
    }

    public function querySTKStatus($context, $checkoutRequestID){

        try {
            $token = $this->plugin->isTestMode($context, 'mpesa')
                ? $this->generateSandBoxToken()
                : $this->generateLiveToken();

            $testUrl = 'https://sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query';
            $liveUrl = 'https://api.safaricom.co.ke/mpesa/stkpushquery/v1/query';
            $businessShortCode = $this->plugin->getSetting($context->getId(), 'mpesaBusinessShortCode');

            $reqUrl = $this->plugin->isTestMode($context, 'mpesa') ? $testUrl : $liveUrl;

            $timestamp = '20' . date(    "ymdhis");
            $passKey = $this->plugin->getSetting($context->getId(), 'mpesaPassKey');
            $password= base64_encode($businessShortCode.$passKey.$timestamp);
            $reqHeaders = [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ];

            $reqBody = [
                'BusinessShortCode' => $businessShortCode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'CheckoutRequestID' => $checkoutRequestID
            ];
            $httpClient = Application::get()->getHttpClient();
            $resp = $httpClient->request('POST', $reqUrl, [
                'headers' => $reqHeaders,
                'json' => $reqBody,
            ]);

            return $resp->getBody();

        } catch (\Exception $e) {
            error_log($e->getMessage());
            return $e->getMessage();
        }
    }
    public function generateLiveToken(){

        $context = Application::get()->getRequest()->getContext();

        $consumerId = $this->plugin->getSetting($context->getId(), 'mpesaConsumerId');
        $consumerSecret = $this->plugin->getSetting($context->getId(), 'mpesaConsumerSecret');

        if(!isset($consumerId) || !isset($consumerSecret)){
            die("LIVE - please declare the consumer key and consumer secret");
        }
        $reqUrl = 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

        $credentials = base64_encode($consumerId . ':' . $consumerSecret);
        $httpClient = Application::get()->getHttpClient();

        try {
            $response = $httpClient->request('GET', $reqUrl, [
                'headers' => [ 'Authorization' => 'Basic ' . $credentials]
            ]);
        } catch (GuzzleException $e) {
            die("Failed generating sandbox token");
        }

        $respBody = $response->getBody()->getContents();
        $decodedResp = json_decode($respBody);
        return $decodedResp->access_token;

    }
    public function generateSandBoxToken(){

        $context = Application::get()->getRequest()->getContext();

        $consumerId = $this->plugin->getSetting($context->getId(), 'mpesaConsumerId');
        $consumerSecret = $this->plugin->getSetting($context->getId(), 'mpesaConsumerSecret');

        if(!isset($consumerId)||!isset($consumerSecret)){
            die("SANDBOX - please declare the consumer key and consumer secret as defined in the documentation");
        }
        $reqUrl = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

        $credentials = base64_encode($consumerId . ':' . $consumerSecret);
        $httpClient = Application::get()->getHttpClient();

        try {
            $response = $httpClient->request('GET', $reqUrl, [
                'headers' => [ 'Authorization' => 'Basic ' . $credentials]
            ]);
        } catch (GuzzleException $e) {
            die("Failed generating sandbox token");
        }


        $respBody = $response->getBody()->getContents();
        $decodedResp = json_decode($respBody);
        return $decodedResp->access_token;
    }

}