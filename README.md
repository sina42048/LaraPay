# LaraPay
Iranian payment gateways , all in one ! (still under development use at your own risk !)

# Supported Drivers
Driver | stability | sandbox_stability | description|
|------------|------------|------------|------------|
|idpay|✅|✅|-
|parspal|✅|❌|this web service sand box has issue and doesnt work , however the sandbox functions is implemented, maybe in the future parspal fix this issue !
|zarinpal|✅|✅|this driver sandbox doesnt work properly but we can simulate this with the help of api key in the parspal documentation
|zibal|✅|✅|-

# Example Usage For Payment Process
### For more information about required field for every driver please refer to that driver documentation page
```php
use Sina42048\LaraPay\LaraRecipt;
use Sina42048\LaraPay\Exception\PaymentRequestException;

Route::get('/payment', function () {
    $bill = new LaraBill();
    $bill->amount(1000);
    $bill->order_id = 2;

    try {
        return LaraPay::setBill($bill)
            ->setDriver('idpay')
            ->prepare(function($transactionId, $driverName) {
                //dd($transactionId); // do database actions
            })
            ->render();
    } catch (PaymentRequestException $e) {
        dd($e->getMessage());
    }
});
```

# Example Usage For Verify Process
```php
use Sina42048\LaraPay\LaraBill;
use Sina42048\LaraPay\Exception\PaymentVerifyException;


Route::match(['GET', 'POST'], '/verify', function() {
    try {
        LaraPay::setDriver('idpay')
            ->checkAmount(function($transactionId) {
                return $amount; // $amount should be return from your table in database based on transaction id
            })
            ->verify(function(LaraRecipt $recipt) {
                dd($recipt); // payment is verfied , recipt data accessable
            });

    } catch (PaymentVerifyException $e) {
        dd($e->getMessage());
    }
});
```
# Contributing
pull request are welcome !

# License
MIT