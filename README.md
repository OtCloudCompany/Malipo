# About Malipo OJS Plugin
An OJS plugin that integrates MPESA and Stripe into OJS payments.

**Malipo** - Swahili word for payments

**Inspiration** - OJS supports only paypal and manual payments when there are so many
payment methods in the world. On top of that, only one paymethod can be enabled at a time.
It being 2024, customers (authors and subscribers) should have the freedom to choose how they want to pay
and not be restricted to paypal and manual methods only. This project is an attempt to add more
options for OJS where customers are presented with multiple options on how to pay, just like we see
on many other platforms.

**WARNING**: This plugin is still under development and testing. 
It is being developed on OJS 3.4 and has not been tested at all on
older versions. Please test the plugin thoroughly before deploying it to a production server.

**TODO**
1. Thoroughly test the plugin including in production modes, find and fix any issues.
2. Add PayPal as a third payment option in the plugin.

## Considerations
1. The plugin requires OJS version 3.4.0-7 that was released on August 23, 2024 or newer. See the issue here https://github.com/pkp/pkp-lib/issues/10327
2. For now, php-curl is required on the server for stripe to work

