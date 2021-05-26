# LaraPay
Iranian Payment Service

# Example Usage For Payment Process
```php
use Sina42048\LaraPay\LaraRecipt;
use Sina42048\LaraPay\Exception\PaymentRequestException;

$bill = new LaraBill();
$bill->amount(1000);
$bill->order_id = 2;

try {
        LaraPay::setBill($bill)
                ->setDriver('idpay')
                ->prepare(function($transactionId, $driverName) {
                // save data in your database
                })
                ->render();
} catch (PaymentRequestException $e) {
        dd($e->getMessage());
}
```

# Example Usage For Verify Process
```php
use Sina42048\LaraPay\LaraBill;
use Sina42048\LaraPay\Exception\PaymentVerifyException;

try {
        LaraPay::setDriver('idpay')->verify(function(LaraRecipt $recipt) {
                dd($recipt); // payment is verfied , now you must check recipt data with your database
        });

} catch (PaymentVerifyException $e) {
        dd($e->getMessage());
}
```