/**
* plugins/paymethod/mpesa/templates/init_payment_intent.tpl
*
* Copyright (c) 2024 HyperLink Consulting Ltd
* Copyright (c) 2024 Otuoma Sanya
* Distributed under the GNU GPL v3.
*
* Mpesa payment plugin
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