<?php

namespace Sina42048\LaraPay;

/**
 * LaraRecipt class
 * @author Sina Fathollaho
 * @package Sina42048\LaraPay
 */
class LaraRecipt {

    /**
     * recipt data holder
     * @var array $data
     */
    private $data;

    /**
     * recipt amount
     * @var int $amount
     */
    private $amount;

    /**
     * payment transaction id
     * @var string $transaction_id
     */
    private $transaction_id;

    /**
     * payment status code
     * @var int $status_code
     */
    private $status_code;


    /**
     * store recipt data
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function __set($key, $value) {
        $this->data[$key] = $value;
    }

    /**
     * get recipt data
     * @param string $key
     * @return mixed
     */
    public function __get($key) {
        return $this->data[$key];
    }

    /**
     * set transaction id
     * @param string $value
     * @return void
     */
    public function setTransactionId($value) {
        $this->transaction_id = $value;
    }

    /**
     * get transaction id
     * @return string
     */
    public function getTransactionId() {
        return $this->transaction_id;
    }

    /**
     * set status code
     * @param int $value
     * @return void
     */
    public function statusCode($value) {
        $this->status_code = $value;
    }

    /**
     * get status code
     * @return int
     */
    public function getStatusCode() {
        return $this->status_code;
    }

    /**
     * set amount
     * @param int $value
     * @return void
     */
    public function amount($value) {
        $this->amount = $value;
    }

    /**
     * get amount
     * @return int
     */
    public function getAmount() {
        return $this->status_code;
    }

}