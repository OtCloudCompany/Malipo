{**
 * plugins/paymethod/mpesa/templates/mpesa_confirm_payment.tpl
 *
 * Copyright (c) 2024 HyperLink DSL
 * Copyright (c) 2024 Otuoma Sanya
 * Distributed under the GNU GPL v3.
 *
 * Mpesa payment page
 *}
{include file="frontend/components/header.tpl" pageTitle="plugins.paymethod.mpesa"}
<div class="page page_payment_form">
    <div class="container">
        <div class="column column-1">
            <img src="{$mpesaLogoUrl}" alt="mpesa logo">
        </div>
        <div class="column column-2 bordered">
            <h1 class="page_title">
                {translate key="plugins.paymethod.mpesa.stkRequestedHeader"}
            </h1>
            <div>
                <p>
                    MPESA request has been sent to <strong>{$phoneNumber}</strong>
                    <ol>
                        <li>Please enter your MPESA PIN on your phone</li>
                        <li>Wait to receive MPESA confirmation SMS </li>
                        <li>Click the button below to confirm payment.</li>
                    </ol>
                </p>

                <form class="pkp_form" id="mpesaConfirmPayment" method="POST"
                      action="{url page="payment" op="plugin" path=$pluginName|to_array:'confirm-payment':$queuedPaymentId}">
                    <p>
                        <input type="hidden" value="{$checkoutReqId}" name="checkoutReqId" />
                        <input type="submit" value="Confirm Payment" class="cmp_button" />
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>

{include file="frontend/components/footer.tpl"}
