# magento2-scanpay

This Magento 2 module allows a webshop to receive payments through the Scanpay payment gateway.

# Module Setup
First you must install the module and then you must configure it.
## Installation
There are multiple ways to install this module:

- Clone this git repository into app/code/scanpay/payment-module in your Magento directory.
- Install the module using the composer CLI `~$ composer require scanpay/paymentmodule`

## Configuration
Follow the following steps to configure the module:

1. In Magento's admin panel navigate to Stores > Configuration > Sales > Payment Methods > Scanpay.
2. Enable the module and enter the API-key from your Scanpay shop settings in the API-key field.

# How does this work?

## /view
The `/view` folder defines the layout and javascript of the module.
The actual HTML form presented for the customer can be found at `/view/frontend/web/template/payment/form.html`.

`/view/frontend/web/js/view/payment/method-renderer/scanpaypaymentmodule.js` defines the javascript which is run when the customer places an order.

This javascript creates the order and makes an AJAX request to the `/Controller/Index/GetPaymentURL.php` containing the order id.

## /Controller
The `/Controller` folder defines the publically served PHP pages.
It's served at an URL defined by `/etc/frontend/routes.xml`.
`/Controller/Index/GetPaymentURL.php` gathers order parameters from Magento and uses `/Model/ScanpayClient.php` to send an request to the Scanpay API.
Upon success it will return a payment url which the webshop customer should be redirected to.

## /Model
The `/Model` folder defines utility classes used by other PHP files.
For instance it contains a money class used to  handle amounts of currency.
It also contains `ScanpayClient.php` which does the actual http request to the Scanpay API using PHP-curl.

## /etc
The `/etc` folder defines module dependencies, module configuration, module naming and the layout of the admin panel module configuration.
