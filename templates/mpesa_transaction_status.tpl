{**
 * plugins/paymethod/malipo/templates/mpesa_transaction_status.tpl
 *
 * Copyright (c) 2024 OtCLoud Company Limited
 * Copyright (c) 2024 Otuoma Sanya
 * Distributed under the GNU GPL v3. For full terms see LICENSE file
 *
 * MPESA and Stripe Payment PLugin for OJS
 *}
{include file="frontend/components/header.tpl" pageTitle="plugins.paymethod.malipo"}
<div class="page page_payment_form">
    <div class="container">
        <div class="column column-1">
            <!-- Content for the first column -->
            <img src="{$mpesaLogoUrl}" alt="mpesa logo">
        </div>
        <div class="column column-2 bordered">
            <h1 class="page_title">
                {translate key="plugins.paymethod.malipo.transactionStatusHeader"}
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
