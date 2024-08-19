{**
 * plugins/paymethod/malipo/templates/generic.tpl
 *
 * Copyright (c) 2024 OtCLoud Company Limited
 * Copyright (c) 2024 Otuoma Sanya
 * Distributed under the GNU GPL v3. For full terms see LICENSE file
 *
 * MPESA and Stripe Payment PLugin for OJS
 *}
{include file="frontend/components/header.tpl" pageTitle="plugins.paymethod.malipo.stripe"}

<div class="page page_payment_form">
    <div class="container">
        <div class="column column-1">
            <img src="{$stripeLogoUrl}" alt="stripe logo">
            <div>Provide your card details</div>
        </div>
        <div class="column column-2">
            <table class="cmp_table">
                <tr>
                    <th>{translate key="plugins.paymethod.malipo.purchase.title"}</th>
                    <td>{$itemName|escape}</td>
                </tr>
                {if $itemAmount}
                    <tr>
                        <th>{translate key="plugins.paymethod.malipo.purchase.fee"}</th>
                        <td>{$itemAmount|string_format:"%.2f"}{if $itemCurrencyCode} ({$itemCurrencyCode|escape}){/if}</td>
                    </tr>
                {/if}
            </table>
            <form class="pkp_form" id="stripePaymentForm" method="POST"
                    action="{url page="payment" op="plugin" path=$pluginName|to_array:'simulate':$queuedPaymentId}"
            >
                <input type="hidden" value="{$pluginName}" name="pluginName" id="pluginName" />
                <input type="hidden" value="stripe" name="gateway" id="gateway" />
                <input type="hidden" value="{$queuedPaymentId}" name="queuedPaymentId" id="queuedPaymentId" />
                <input type="hidden" value="{$itemName}" name="itemName" id="itemName" />
                <input type="hidden" value="{$contextPath}" name="contextPath" id="contextPath" />
                <input type="hidden" value="{$stripeSubmitUrl}" name="stripeSubmitUrl" id="stripeSubmitUrl" />
            </form>
            <div id="checkoutElem"></div>
        </div>
    </div>
</div>

{include file="frontend/components/footer.tpl"}
