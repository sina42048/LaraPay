<?php

namespace Sina42048\LaraPay\Abstract;

use Illuminate\Support\Facades\View;
use \GuzzleHttp\Client;

/**
 * class Driver
 * @author Sina Fathollahi
 * @package Sina42048\LaraPay\Abstract
 */
abstract class Driver {

    /**
     * hold driver name
     * @var string driver name
     */
    protected $driverName;
    
    /**
     * hold specified driver config
     * @var array $config 
     */
    protected $config;

    /**
     * hold LaraBill class data
     * @var \Sina42048\LaraPay\LaraBill $bill
     */
    protected $bill;

    /**
     * guzzle http client instance
     * @var \GuzzleHttp\Client $client
     */
    protected $client;

    /**
     * request payment retrived data
     * @var array $data
     */
    protected $data;

    /**
     * @param string $driverName
     * @param string $config specified driver config
     * @param \Sina42048\LaraPay\LaraBill $bill bill instance
     * @return void
     */
    public function __construct(string $driverName, array $config, \Sina42048\LaraPay\LaraBill $bill = null) {
        $this->driverName = $driverName;
        $this->config = $config;
        $this->bill = $bill;
        $this->client = new Client();
    }
    
    /**
     * render payment view
     * @return Illuminate\Support\Facades\View
     */
    public function render() {
        return View::make('larapay::payment', [
            'method' => 'POST',
            'inputs' => $this->data,
            'url' => $this->data['link'],
        ]);
    }

    /**
     * set amount from user own database by transaction id 
     * @param callback $func
     * @return self
     */
    public abstract function checkAmount(callable $func);

    /**
     * request payment to web service
     * @param callback $func
     * @throws \Sina42048\LaraPay\Exception\PaymentRequestException
     * @return self
     */
    public abstract function pay(callable $func);

    /**
     * verify payment
     * @throws Sina42048\LaraPay\Exception\PaymentVerifyException
     * @param callback $func
     * @return $func
     */
    public abstract function verify(callable $func);

    /**
     * determine which permissions should be passed to access the verify page
     * @return bool
     */
    protected abstract function verifyAccessPermissions();

    /**
     * create recipt data
     * @param array $reciptData
     * @return Sina42048\LaraPay\LaraRecipt
     */
    protected abstract function createRecipt($reciptData);

    /**
     * translate messages that web service api retrive
     * @param int $errorCode error code
     * @return string
     */
    protected abstract function translateErrorMessages($errorCode);

    /**
     * translate status codes that web service retrive
     * @param int status_code
     * @return string
     */
    protected abstract function translateStatusCode($statusCode);

    /**
     * failed status codes that web service api can retrive after customer payment process
     * @return array
     */
    protected abstract function failedPaymentStatusCodes();
}