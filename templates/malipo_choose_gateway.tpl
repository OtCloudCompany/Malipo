{**
 * plugins/paymethod/malipo/templates/malipo_choose_gateway.tpl
 *
 * Copyright (c) 2024 OtCLoud Company Limited
 * Copyright (c) 2024 Otuoma Sanya
 * Distributed under the GNU GPL v3. For full terms see LICENSE file
 *
 * MPESA and Stripe Payment PLugin for OJS
 *}
{include file="frontend/components/header.tpl" pageTitle="plugins.paymethod.malipo.landingPage"}

<div class="page page_payment_form">

    <h1 class="page_title">
        {translate key="plugins.paymethod.malipo.landingPageTitle"}
    </h1>
                      
    <div class="payment-details mpesa-carrier">
        <table class="cmp_table">
            <tr>
                <th>{translate key="plugins.paymethod.malipo.paymentFor"}</th>
                <td>{$itemName|escape}</td>
            </tr>
            {if $itemAmount}
                <tr>
                    <th>{translate key="plugins.paymethod.malipo.purchase.fee"}</th>
                    <td>{$itemAmount|string_format:"%.2f"}{if $itemCurrencyCode} ({$itemCurrencyCode|escape}){/if}</td>
                </tr>
            {/if}
        </table>
    </div>

    <div class="stripe-carrier">

        <div class="container">

            <div class="column column-1">
                <img src="{$mpesaLogoUrl}" alt="mpesa logo" class="mpesa-logo">
                <div>Click on continue to use mpesa payment</div>
                <form class="pkp_form" id="mpesaRequestPayment" method="POST" action="">
                    <input type="hidden" value="{$pluginName}" name="pluginName" />
                    <input type="hidden" value="mpesa" name="gateway" />
                    <input type="hidden" value="{$queuedPaymentId}" name="queuedPaymentId" />
                    <input type="hidden" value="{$itemName}" name="itemName" />
                    <button type="submit" class="cmp_button margin-y">Continue with MPesa</button>
                </form>
            </div>
            <div class="column column-2">
                <img src="{$stripeLogoUrl}" alt="stripe logo" class="stripe-logo">
                <div>Click on continue to use stripe card payment</div>
                <form class="pkp_form" id="stripePaymentForm" method="POST" action="">
                    <input type="hidden" value="{$pluginName}" name="pluginName" />
                    <input type="hidden" value="stripe" name="gateway" />
                    <input type="hidden" value="{$queuedPaymentId}" name="queuedPaymentId" />
                    <input type="hidden" value="{$itemName}" name="itemName" />
                    <input type="hidden" value="{$contextPath}" name="contextPath" id="contextPath" />
                    <button type="submit" class="cmp_button margin-y">Continue with Stripe</button>
                </form>
            </div>
        </div>

    </div>
</div>


{include file="frontend/components/footer.tpl"}
