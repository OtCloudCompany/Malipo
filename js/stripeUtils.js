/**
* plugins/paymethod/malipo/js/stripeUtils.js
*
* Copyright (c) 2024 OtCloud Company Limited
* Copyright (c) 2024 Otuoma Sanya
* Distributed under the GNU GPL v3, see LICENSE
*
* Malipo: Stripe and MPESA payment plugin for OJS
*/
function getData($) {
    $(document).ready(function () {

        const stripeSubmitUrl = $("#stripeSubmitUrl").val();

        $.ajax({
            url: stripeSubmitUrl,
            success: async function(result){
                // window.alert(result);
                $("#generic").html(result);
                const resp = JSON.parse(result);

                const clientSecret = resp.clientSecret;
                const publishableKey = resp.publishableKey;
                const sessionId = resp.sessionId;

                const stripe = Stripe(publishableKey);
                const checkout = await stripe.initEmbeddedCheckout({clientSecret});

                // Mount Checkout
                checkout.mount("#checkoutElem");
            }
        });
    });
}

getData($);