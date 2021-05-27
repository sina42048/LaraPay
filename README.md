# LaraPay
Iranian Payment (Under Development)

# Example Usage For Payment Process
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

Route::post('/verify', function() {
    try {
        LaraPay::setDriver('idpay')
            ->checkAmount(function($transactionId) {
                return $amount; // $amount should be return from your table in database based on transaction id
            })
            ->verify(function(LaraRecipt $recipt) {
                dd($recipt); // payment is verfied and you access to additional recipt data
            });

    } catch (PaymentVerifyException $e) {
        dd($e->getMessage());
    }
});
```