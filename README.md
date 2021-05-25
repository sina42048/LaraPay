# LaraPay
Iranian Payment Service

# Example Usage
```php
use Sina42048\LaraPay\LaraBill;

// example for idpay driver
$bill = new LaraBill();
$bill->amount = 1000;
$bill->order_id = 2;

LaraPay::setBill($bill)
        ->setDriver('idpay')
        ->prepare(function($transactionId, $driverName) {
			// save transaction id and driver name in database
        })
        ->render();
```