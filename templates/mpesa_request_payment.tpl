{**
 * plugins/paymethod/mpesa/templates/mpesa_request_payment.tpl
 *
 * Copyright (c) 2024 HyperLink Consulting Ltd
 * Copyright (c) 2024 Otuoma Sanya
 * Distributed under the GNU GPL v3.
 *
 * Mpesa payment plugin
 *}
{include file="frontend/components/header.tpl" pageTitle="plugins.paymethod.malipo.mpesa"}

<div class="page page_payment_form">
    <div class="container">
        <div class="column column-1">
            <img src="{$mpesaLogoUrl}" alt="mpesa logo">
            <div>Enter a phone number (starting with 254...) that will be prompted to pay</div>
        </div>
        <div class="column column-2">
            <h1 class="page_title">
                {translate key="plugins.paymethod.malipo.mpesa"}
            </h1>

            <form class="pkp_form" id="mpesaRequestPayment" method="POST"
                    action="{url page="payment" op="plugin" path=$pluginName|to_array:'simulate':$queuedPaymentId}"
            >
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

                    <tr>
                        <th>{translate key="plugins.paymethod.malipo.purchase.phoneNumber"}</th>
                        <td>
                            <input type="text" id="phoneNumber" name="phoneNumber"
                                   value="{$paymentForm.phoneNumber|default:''}254" required>
                            {if $errors.phoneNumber}
                                <span class="error">{$errors.phoneNumber}</span>
                            {/if}
                        </td>
                    </tr>
                </table>
                <p>
                    <input type="submit" value="Send Request" class="cmp_button" />
                </p>

            </form>
        </div>
    </div>
</div>


{include file="frontend/components/footer.tpl"}
