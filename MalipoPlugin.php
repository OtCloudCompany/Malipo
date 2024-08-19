<?php
/**
 * @file plugins/paymethod/malipo/MalipoPlugin.php
 *
 * Copyright (c) 2024 OtCloud Company LTD
 * Copyright (c) 2024 Otuoma Sanya
 * Distributed under the GNU GPL v3.
 * Resource: https://stripe.com/docs/payments/accept-a-payment?platform=web&ui=embedded-checkout
 * @class MalipoPlugin
 * @brief Mpesa and Stripe payment for OJS
 */
namespace APP\plugins\paymethod\malipo;

use APP\core\Application;
use APP\plugins\paymethod\malipo\Utilities;
use APP\template\TemplateManager;
use PKP\db\DAORegistry;
use PKP\form\Form;
use PKP\plugins\Hook;
use PKP\plugins\PaymethodPlugin;
use Stripe\Exception\ApiErrorException;

class MalipoPlugin extends PaymethodPlugin {

    public function register($category, $path, $mainContextId = NULL): bool{

        $success = parent::register($category, $path, $mainContextId);

        if ($success && $this->getEnabled()) {
            $this->addLocaleData();
            Hook::add('Form::config::before', [$this, 'addSettings']);

            $request = Application::get()->getRequest();

            $templateMgr = TemplateManager::getManager($request);
            $mpesaLogoUrl = $request->getBaseUrl() . '/' . $this->getPluginPath() . '/images/mpesa-logo.png';
            $stripeLogoUrl = $request->getBaseUrl() . '/' . $this->getPluginPath() . '/images/stripe-logo.png';
            $stripeUtilsUrl = $request->getBaseUrl() . '/' . $this->getPluginPath() . '/js/stripeUtils.js';

            // Create PaymentIntent and add Stripe.js only if we have stripe as gateway
            $reqMethod = $this->getRequest()->getRequestMethod();
            $gateway = $this->getRequest()->getUserVar('gateway');
            if ($reqMethod == 'POST' && isset( $gateway ) && $gateway == 'stripe'){
//                $templateMgr->assign('stripePublishableKey', $mpesaLogoUrl);
                $templateMgr->addJavaScript('stripeJs', 'https://js.stripe.com/v3/');
                $templateMgr->addJavaScript('stripeUtils', $stripeUtilsUrl);
            }

            $templateMgr->assign('mpesaLogoUrl', $mpesaLogoUrl);
            $templateMgr->assign('stripeLogoUrl', $stripeLogoUrl);
            $styleUrl = $request->getBaseUrl() . '/' . $this->getPluginPath() . '/css/style.css';
            $templateMgr->addStyleSheet('malipoStyles', $styleUrl);
        }
        return $success;
    }


    /**
     * Get the payment form for this plugin.
     *
     * @param \PKP\context\Context $context
     * @param \PKP\payment\QueuedPayment $queuedPayment
     *
     * @return \PKP\form\Form
     */
    public function getPaymentForm($context, $queuedPayment): Form {

        $reqMethod = $this->getRequest()->getRequestMethod();
        $submitUrl = $this->getRequest()->url(null, 'payment', 'plugin', [$this->getName(), 'daraja-callback'], ['queuedPaymentId' => $queuedPayment->getId()]);
        $stripeSubmitUrl = null;

        switch ($reqMethod){
            case 'POST': // retrieve form data

                $gateway = $this->getRequest()->getUserVar('gateway');

                if ($gateway == 'mpesa'){
                    $paymentForm = new Form($this->getTemplateResource('mpesa_request_payment.tpl'));
                }else if ($gateway == 'stripe'){
                    $paymentForm = new Form($this->getTemplateResource('init_payment_intent.tpl'));
                    $stripeSubmitUrl = $this->getRequest()->url(null, 'payment', 'plugin', [$this->getName(), 'init-payment-intent', $queuedPayment->getId()]);
                }
                break;
            default:
                $paymentForm = new Form($this->getTemplateResource('malipo_choose_gateway.tpl'));
        }

        $paymentManager = Application::getPaymentManager($context);

        $paymentForm->setData([
            'itemName' => $paymentManager->getPaymentName($queuedPayment),
            'itemAmount' => $queuedPayment->getAmount() > 0 ? $queuedPayment->getAmount() : null,
            'itemCurrencyCode' => $queuedPayment->getAmount() > 0 ? $queuedPayment->getCurrencyCode() : null,
            'queuedPaymentId' => $queuedPayment->getId(),
            'pluginName' => $this->getName(),
            'submitUrl' => $submitUrl,
            'reqMethod' => $reqMethod,
            'contextPath' => $context->getPath(),
            'stripeSubmitUrl' => $stripeSubmitUrl,
        ]);
        return $paymentForm;
    }

    /**
     * Must be implemented in a child class to save payment settings and attach updated data to the response
     */
    public function saveSettings(string $hookname, array $args): void {
        $slimRequest = $args[0];
        $request = $args[1];
        $updatedSettings = $args[3];

        $allParams = $slimRequest->getParsedBody();
        $saveParams = [];
        foreach ($allParams as $param => $val) {
            switch ($param) {
                case 'stripeTestMode':
                case 'mpesaTestMode':
                    $saveParams[$param] = $val === 'true';
                    break;
                case 'mpesaConsumerId':
                case 'mpesaPassKey':
                case 'mpesaBusinessShortCode':
                case 'mpesaConsumerSecret':
                case 'stripePublishableKey':
                case 'stripeSecretKey':
                    $saveParams[$param] = (string) $val;
                    break;
            }
        }
        $contextId = $request->getContext()->getId();
        foreach ($saveParams as $param => $val) {
            $this->updateSetting($contextId, $param, $val);
            $updatedSettings->put($param, $val);
        }
    }

    public function addSettings($hookName, $form): void {

        import('lib.pkp.classes.components.forms.context.PKPPaymentSettingsForm'); // Load constant

        $context = Application::get()->getRequest()->getContext();
        if ($form->{'id'} !== FORM_PAYMENT_SETTINGS || !$context) {
            return;
        }
        $form->addGroup([
            'id' => 'mpesaPayment',
            'label' => 'MPESA Fee Payment',
            'showWhen' => 'paymentsEnabled',
        ])
            ->addField(new \PKP\components\forms\FieldOptions('mpesaTestMode', [
                'label' => 'Test mode',
                'options' => [
                    ['value' => true, 'label' => __('common.enable')]
                ],
                'value' => (bool) $this->getSetting($context->getId(), 'mpesaTestMode'),
                'groupId' => 'mpesaPayment',
            ]))
            ->addField(new \PKP\components\forms\FieldText('mpesaConsumerId', [
                'label' => 'Consumer ID',
                'value' => $this->getSetting($context->getId(), 'mpesaConsumerId'),
                'groupId' => 'mpesaPayment',
            ]))
            ->addField(new \PKP\components\forms\FieldText('mpesaConsumerSecret', [
                'label' => 'Consumer Secret',
                'value' => $this->getSetting($context->getId(), 'mpesaConsumerSecret'),
                'groupId' => 'mpesaPayment'
            ]))
            ->addField(new \PKP\components\forms\FieldText('mpesaPassKey', [
                'label' => 'Pass Key',
                'value' => $this->getSetting($context->getId(), 'mpesaPassKey'),
                'groupId' => 'mpesaPayment',
            ]))
            ->addField(new \PKP\components\forms\FieldText('mpesaBusinessShortCode', [
                'label' => 'Business Short Code',
                'value' => $this->getSetting($context->getId(), 'mpesaBusinessShortCode'),
                'groupId' => 'mpesaPayment',
            ]));

        $form->addGroup([
            'id' => 'stripePayment',
            'label' => 'Stripe Fee Payment',
            'showWhen' => 'paymentsEnabled',
        ])
            ->addField(new \PKP\components\forms\FieldOptions('stripeTestMode', [
                'label' => 'Test mode',
                'options' => [
                    ['value' => true, 'label' => __('common.enable')]
                ],
                'value' => (bool) $this->getSetting($context->getId(), 'stripeTestMode'),
                'groupId' => 'stripePayment',
            ]))
            ->addField(new \PKP\components\forms\FieldText('stripePublishableKey', [
                'label' => 'Publishable Key',
                'value' => $this->getSetting($context->getId(), 'stripePublishableKey'),
                'groupId' => 'stripePayment',
            ]))
            ->addField(new \PKP\components\forms\FieldText('stripeSecretKey', [
                'label' => 'Secret Key',
                'value' => $this->getSetting($context->getId(), 'stripeSecretKey'),
                'groupId' => 'stripePayment',
            ]));
    }

    /**
     * Handle incoming requests/notifications
     * @param array $args
     * @param \APP\core\Request $request
     * @throws ApiErrorException
     */
    public function handle($args, $request){
        $journal = $request->getJournal();
        $queuedPaymentDao = DAORegistry::getDAO('QueuedPaymentDAO');
        $paymentManager = Application::getPaymentManager($journal);
        $queuedPaymentId = $args[1];
        $templateMgr = TemplateManager::getManager($request);

        $darajaCallback = $this->getRequest()->url(null, 'payment', 'plugin', [$this->getName(), 'daraja-callback', $queuedPaymentId], []);

        $action = $args[0];

        $queuedPayment = $queuedPaymentDao->getById($queuedPaymentId);

        // queuedPayment is already fulfilled when confirming payment
        if (!$queuedPayment && $action !== 'confirm-payment') {
            throw new \Exception("Invalid queued payment ID {$queuedPaymentId}!");
        }

        $utilities = new Utilities($this);

        //MPESA Handlers
        if ($action == 'simulate'){

            if ($request->isGet()) {
                throw new \Exception("Invalid request in simulate stkPush");
            }

            try {

                $businessShortCode = $this->getSetting($journal->getId(), 'mpesaBusinessShortCode');
                $amount = $queuedPayment->getAmount();
                $partyA = $request->getUserVar('phoneNumber');
                $partyB = $businessShortCode;
                $phoneNumber = $partyA;
                $callBackUrl = $darajaCallback;

                $accountReference = $paymentManager->getPaymentName($queuedPayment);
                $transactionDesc  = $accountReference;

                $utilities = new Utilities($this);

                $stkPush = $utilities->STKPush($businessShortCode, $amount, $partyA, $partyB, $phoneNumber, $callBackUrl, $accountReference, $transactionDesc);
                $decoded_resp = json_decode($stkPush);

                $checkoutReqId   = $decoded_resp->{'CheckoutRequestID'};
                $respDescription = $decoded_resp->{'ResponseDescription'};

                if ($decoded_resp->{'ResponseCode'} == 0){ //success, accepted for processing

                    $templateMgr = TemplateManager::getManager($request);

                    $templateMgr->assign("phoneNumber", $partyA);
                    $templateMgr->assign("pluginName", $this->getName());
                    $templateMgr->assign("queuedPaymentId", $queuedPaymentId);
                    $templateMgr->assign("checkoutReqId", $checkoutReqId);
                    $templateMgr->display(
                        $this->getTemplateResource('mpesa_confirm_payment.tpl')
                    );
                }else{ // an error occurred
                    throw new \Exception($checkoutReqId. " FAILED: " . $respDescription);
                }

            } catch (\Exception $e) {
                error_log('MPESA transaction exception: ' . $e->getMessage());
                $templateMgr = TemplateManager::getManager($request);
                $templateMgr->assign('message', 'plugins.paymethod.malipo.error');
                $templateMgr->display($this->getTemplateResource('frontend/pages/message.tpl'));
            }
        }
        if ($action == 'daraja-callback'){ //handle the callback from daraja

            error_log("========Daraja callback called===========");

            $callbackResp = file_get_contents('php://input');
            $decodedResp = json_decode($callbackResp)->{'Body'}->{'stkCallback'};

            $mpesaReqId = $decodedResp->{'CheckoutRequestID'};

            if($decodedResp->{'ResultCode'} == '0'){

                $paymentManager->fulfillQueuedPayment($request, $queuedPayment, $this->getName());

                $callbackMetadataItems = $decodedResp->{'CallbackMetadata'}->{'Item'};
                $receiptNumber = null;

                foreach ($callbackMetadataItems as $item) {
                    if($item->{'Name'} === 'MpesaReceiptNumber') {
                        $receiptNumber = $item->{'Value'};
                        break;
                    }
                }
                if ($receiptNumber) {
                    $this->addSettings('mpesa_receipt_for'.$mpesaReqId, $receiptNumber);
                }

            }else{
                error_log("MPESA CheckoutRequestID: ".$mpesaReqId);
                error_log($decodedResp->{'ResultDesc'});
            }
        }
        if ($action == 'confirm-payment'){ //query the transaction status

            error_log(" ======= confirming MPESA payment ========");

            if ($request->isGet()) {
                throw new \Exception("Invalid request in confirm-payment");
            }

            $utilities = new Utilities($this);
            $checkoutReqId = $request->getUserVar('checkoutReqId');
            $transactionStatus = $utilities->querySTKStatus($journal, $checkoutReqId);

            $templateMgr = TemplateManager::getManager($request);

            $decodedResp = json_decode($transactionStatus);
            $resultCode = null;

            if ($decodedResp->{'ResponseCode'} == '0') { $resultCode = $decodedResp->{'ResultCode'}; }

            if ($resultCode == '0'){
                $templateMgr->assign('respMsg', __('plugins.paymethod.malipo.transactionSucceeded'));
            }else{
                $templateMgr->assign('failedMsg', __('plugins.paymethod.malipo.transactionFailed'));
                $templateMgr->assign('respMsg', $decodedResp->{'ResultDesc'});
            }
            $templateMgr->display(
                $this->getTemplateResource('mpesa_transaction_status.tpl')
            );

        }

        // Stripe handlers
        if ($action == 'init-payment-intent'){ //

            $stripeSession = $utilities->initPaymentSession($queuedPayment);
            $stripeClientSecret = $stripeSession->client_secret;
            $publishableKey = $this->getSetting($this->getCurrentContextId(), 'stripePublishableKey');

            echo json_encode(
                array(
                    'clientSecret' => $stripeClientSecret,
                    'publishableKey' => $publishableKey,
                )
            );
        }

        if ($action == 'stripe-callback'){

            error_log("======== Stripe callback called =========");

            $session = $utilities->retrieveSessionStatus($request);

            if ($session->status == 'open'){
                $request->redirect(null, 'payment', 'plugin', [$this->getName()], ['queuedPaymentId' => $queuedPayment->getId()]);
            }else if($session->status == 'complete'){
                $paymentManager->fulfillQueuedPayment($request, $queuedPayment, $this->getName());
            }

            $templateMgr->assign('sessionStatus', $session->status);
            $templateMgr->assign('amountTotal', $session->amount_total);
            $templateMgr->assign('currency', $session->currency);
            $templateMgr->assign('paymentName', $paymentManager->getPaymentName($queuedPayment));

            $templateMgr->display(
                $this->getTemplateResource('stripe_callback_response.tpl')
            );
        }

        return null;
    }

    /**
     * Get the display name for this plugin.
     *
     * @return string
     */
    public function getDisplayName(): string{
        return 'Malipo Payments';
    }

    public function getName(): string{
        return 'MalipoPayments';
    }

    /**
     * Get a description of this plugin.
     *
     * @return string
     */
    public function getDescription(): string {
        // TODO: Implement getDescription() method.
        return "Integrates Stripe and MPesa Payment options into OJS. Tested on OJS 3.4";
    }

    /*
    *  $payMethod is either "mpesa" or "stripe"
    */
    public function isTestMode($context, $payMethod): bool {

        if (!$context) return false;

        if ($this->getSetting($context->getId(), $payMethod.'TestMode') == '1') {
            return true;
        }
        return false;
    }
}
