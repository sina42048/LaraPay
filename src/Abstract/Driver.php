<?php

namespace Sina42048\LaraPay\Abstract;

use \GuzzleHttp\Client;

/**
 * class Driver
 * @author Sina Fathollahi
 * @package Sina42048\LaraPay\Abstract
 */
abstract class Driver {

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
     * @param string $config specified driver config
     * @param \Sina42048\LaraPay\LaraBill $bill bill instance
     * @return void
     */
    public function __construct(array $config, \Sina42048\LaraPay\LaraBill $bill) {
        $this->config = $config;
        $this->bill = $bill;
        $this->client = new Client();
    }

    /**
     * request payment to web service
     * @throws \Sina42048\LaraPay\Exception\PaymentRequestException
     * @return self
     */
    public abstract function pay();

    /**
     * translate messages that web service api retrive
     * @param int $statusCode error status code
     * @param int $errorCode error code
     * @return string
     */
    protected abstract function translateErrorMessages($statusCode, $errorCode);

    /**
     * render payment view
     * @return Illuminate\Support\Facades\View
     */
    public abstract function render();
}