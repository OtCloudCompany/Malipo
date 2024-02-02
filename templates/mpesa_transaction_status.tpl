{**
 * plugins/paymethod/mpesa/templates/mpesa_transaction_status.tpl
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
            <!-- Content for the first column -->
            <img src="{$mpesaLogoUrl}" alt="mpesa logo">
        </div>
        <div class="column column-2 bordered">
            <h1 class="page_title">
                {translate key="plugins.paymethod.mpesa.transactionStatusHeader"}
            </h1>
            <div>
                {if $failedMsg}
                    <div class="text-danger">
                        <h4>{$failedMsg}</h4>
                    </div>
                {/if}
                {$respMsg}
            </div>
        </div>
    </div>
</div>
{include file="frontend/components/footer.tpl"}
