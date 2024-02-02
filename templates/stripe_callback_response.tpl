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

        <h1 class="page_title">
            {translate key="plugins.paymethod.malipo.stripeResponse"}
        </h1>

        <table class="cmp_table">
            <tr>
                <th>{translate key="plugins.paymethod.malipo.paymentStatus"}</th>
                {if sessionStatus == 'complete'}
                    <td>{translate key="plugins.paymethod.malipo.paymentSucceeded"}</td>
                {else}
                    <td>{translate key="plugins.paymethod.malipo.paymentFailed"}</td>
                {/if}
            </tr>

            <tr>
                <th>{translate key="plugins.paymethod.malipo.amount"}</th>
                <td>{$amountTotal} {$currency}</td>
            </tr>

            <tr>
                <th>{translate key="plugins.paymethod.malipo.paymentName"}</th>
                <td>{$paymentName}</td>
            </tr>

        </table>
    </div>
</div>


{include file="frontend/components/footer.tpl"}
