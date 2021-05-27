<?php

namespace Sina42048\LaraPay;

use Sina42048\LaraPay\Exception\BillClassException;
use Sina42048\LaraPay\Exception\DriverNotFoundException;
use Sina42048\LaraPay\Abstract\Driver as AbstractDriver;

/**
 * class LaraPay
 * @author Sina Fathollahi
 * @package Sina42048\LaraPay
 */
class LaraPay {

    /**
     * larapay config array holder
     * @var array $config
     */
    private $config;

    /**
     * driver that used for payment service
     * @var \Sina42048\LaraPay\Abstract\Driver $driver
     */
    private $driver;

    /**
     * hold LaraBill class data
     * @var \Sina42048\LaraPay\LaraBill $bill
     */
    private $bill;

    /**
     * set the initial config array
     * @param array $config config array
     * @return void
     */
    public function __construct(array $config) {
        $this->config = $config;
    }

    /**
     * set bill class that used for hold payment data
     * @param \Sina42048\LaraPay\LaraBill $bill instance of larabill class
     * @throws \Sina42048\LaraPay\Exception\BillClassException if bill param not instance of bill class
     * @return self
     */
    public function setBill(\Sina42048\LaraPay\LaraBill $bill) {
        if ($bill instanceof \Sina42048\LaraPay\LaraBill) {
            $this->bill = $bill;
            return $this;
        }
        throw new BillClassException();
    }

    /**
     * set web service driver that use for payment
     * @param string $driverName web service name
     * @return self
     */
    public function setDriver(string $driverName) {
        if ($this->isDriverExist($driverName)) {
            $this->createDriverInstance($driverName);
            return $this;
        }
    }

    /**
     * prepare payment process
     * @param callback $func
     * @return Sina42048\LaraPay\Abstract\Driver
     */
    public function prepare(callable $func) {
        return $this->driver->pay($func);
    }

    /**
     * check amount for verify process
     * @param callback $func
     * @return Sina42048\LaraPay\Abstract\Driver
     */
    public function checkAmount(callable $func)
    {
        return $this->driver->checkAmount($func);
    }

    /**
     * check if given driver name exist in config array
     * @param string $driverName driver name
     * @throws \Sina42048\LaraPay\Exception\DriverNotFoundException
     * @return bool
     */
    private function isDriverExist(string $driverName) {
        if (!array_key_exists($driverName, $this->config)) {
            throw new DriverNotFoundException('driver not found');
        }

        $reflection = new \ReflectionClass($this->config[$driverName]['class']);
        if (!$reflection->isSubClassOf(AbstractDriver::class)) {
            throw new DriverNotFoundException('selected driver should extend \Sina42048\LaraPay\Abstract\Driver class');
        }

        return true;
    }

    /**
     * create driver instance and set the class driver property
     * @param string $driverName driver name
     * @return void
     */
    private function createDriverInstance(string $driverName) {
        $className = $this->config[$driverName]['class'];

        if (class_exists($className)) {
            $instance = new $className($driverName, $this->config[$driverName], $this->bill);
            $this->driver = $instance;
        }
    }

}