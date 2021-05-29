# LaraPay
Iranian payment gateways for laravel , all in one !

# Install
    Step 1 :
    composer require sina42048/lara-pay
    Step 2 :
    php artisan vendor:publish
    Step 3 :
    configure your api key in the config/larapay.php
------------------------------------------------------
for laravel version 5.5 and below following step is required

    In your config/app.php file add these two lines
```php
// In your providers array.
'providers' => [
    ...
    Sina42048\LaraPay\Provider\LaraPayServiceProvider::class,
],

// In your aliases array.
'aliases' => [
    ...
    'LaraPay' => Sina42048\LaraPay\Facade\Lapay::class,
],
```
# Supported Drivers
Driver | stability | sandbox_stability | description|
|------------|------------|------------|------------|
|idpay|✅|✅|-
|parspal|✅|❌|this web service sand box has issue and doesnt work , however the sandbox functions is implemented, maybe in the future parspal fix this issue !
|zarinpal|✅|✅|this driver sandbox doesnt work properly but we can simulate this with the help of api key in the parspal documentation
|zibal|✅|✅|-
|nextpay|✅|❌|this web service doesnt support sandbox feature

# Example Usage For Payment Process
### For more information about required field for every driver please refer to that driver documentation page
```php
use Sina42048\LaraPay\LaraBill;
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
use Sina42048\LaraPay\LaraRecipt;
use Sina42048\LaraPay\Exception\PaymentVerifyException;


Route::match(['GET', 'POST'], '/verify', function() {
    try {
        LaraPay::setDriver('idpay')
            ->checkAmount(function($transactionId) {
                return $amount; // $amount should be return from your table in database based on transaction id, throw exception if amount not found
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