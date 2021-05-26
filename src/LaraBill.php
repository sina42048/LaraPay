<?php

namespace Sina42048\LaraPay;

/**
 * class LaraBill
 * @author Sina Fathollahi
 * @package Sina42048\LaraPay
 */
class LaraBill {

    /**
     * array list of bill data
     * @var array $data
     */
    private $data;

    /**
     * transaction id that retrive from web service
     * @var string $transaction_id
     */
    private $transaction_id;

    /**
     * amount
     * @var int $amount
     */
    private $amount;


    /**
     * magic method to set bill data
     * @param string $key 
     * @param string $value
     */
    public function __set($key, $value) {
        $this->data[$key] = $value;
    }

    /**
     * magic method to retrive bill data
     * @param string $key
     * @return mixed
     */
    public function __get($key) {
        return $this->data[$key] ?? '';
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
     * retrive transaction id
     * @return string
     */
    public function getTransactionId() {
        return $this->transaction_id;
    }

    /**
     * set amount
     * @param int $value
     * @return void 
     */
    public function amount($value) {
        $this->amiunt = $value;
    }
    
    /**
     * retrive amount
     * @return int
     */
    public function getAmount() {
        return $this->amount;
    }

}