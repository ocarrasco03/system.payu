<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PayU Testing
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
     */

    'payu_test' => env('PAYU_ON_TESTING', true),

    /*
    |--------------------------------------------------------------------------
    | PayU Merchant
    |--------------------------------------------------------------------------
    |
    | This value determines the "merchant id" your application is currently
    | running in. Set this in your ".env" file.
    |
     */

    'payu_merchant' => env('PAYU_MERCHANT_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | PayU Account
    |--------------------------------------------------------------------------
    |
    | Here we can configure your PayU credentials
    |
     */

    'payu_login' => env('PAYU_API_LOGIN', ''),
    'payu_key' => env('PAYU_API_KEY', ''),
    'payu_account' => env('PAYU_ACCOUNT_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | PayU API Variables
    |--------------------------------------------------------------------------
    |
    | This values sets your country for the payment process and set the
    | redirect url before the payment sent.
    |
     */

    'payu_country' => env('PAYU_COUNTRY', ''),
    'pse_redirect' => env('PSE_REDIRECT_URL', 'https://www.systemtour.com/mx/panel/hoteles'),

];
