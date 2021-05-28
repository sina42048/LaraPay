<?php

/*
|--------------------------------------------------------------------------
| LaraPay Drivers
|--------------------------------------------------------------------------
|
| Here is where you can register drivers
|
*/
return [
    'idpay' => [
        'class' => \Sina42048\LaraPay\Driver\IdPay\IdPay::class,
        'api_key' => 'xxxxx',
        'callback_url' => 'http://localhost.dev',
        'payment_request_url' => 'https://api.idpay.ir/v1/payment',
        'payment_verification_url' => 'https://api.idpay.ir/v1/payment/inquiry',
        'sand_box' => false,
        ],
    'zarinpal' => [
        'class' => \Sina42048\LaraPay\Driver\ZarinPal\ZarinPal::class,
        'merchant_id' => 'xxxxx',
        'callback_url' => 'http://localhost.dev',
        'payment_start_url' => 'https://www.zarinpal.com/pg/StartPay',
        'payment_request_url' => 'https://api.zarinpal.com/pg/v4/payment/request.json',
        'payment_verify_url' => 'https://api.zarinpal.com/pg/v4/payment/verify.json',
        'sand_box' => false
    ],
    'parspal' => [
        'class' => \Sina42048\LaraPay\Driver\ParsPal\ParsPal::class,
        'api_key' => 'xxxxx',
        'callback_url' => 'http://localhost.dev',
        'payment_request_url' => 'https://api.parspal.com/v1/payment/request',
        'payment_verify_url' => ' https://api.parspal.com/v1/payment/verify',
        'payment_sand_box_request_url' => 'https://sandbox.api.parspal.com/v1/payment/request',
        'payment_sand_box_verify_url' => ' https://sandbox.api.parspal.com/v1/payment/verify',
        'sand_box' => false
    ]

];