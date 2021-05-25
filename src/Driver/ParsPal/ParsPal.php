<?php

namespace Sina42048\LaraPay\Driver\ParsPal;

use Sina42048\LaraPay\Abstract\Driver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\View;
use Sina42048\LaraPay\Exception\PaymentRequestException;

/**
 * class IdPay
 * @author Sina Fathollahi
 * @package Sina42048\LaraPay\Driver
 */
class ParsPal extends Driver{

    /**
     * {@inheritdoc}
     */
    public function pay() {
        // TODO
    }

    /**
     * {@inheritdoc}
     */
    public function render() {
        // TODO
    }

    /**
     * {@inheritdoc}
     */
    protected function translateErrorMessages($statusCode, $errorCode) {
        // TODO
    }
    
}