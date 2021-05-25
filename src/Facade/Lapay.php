<?php

namespace Sina42048\LaraPay\Facade;

use Illuminate\Support\Facades\Facade;

/**
 * Class Lapay
 * @author Sina Fathollahi
 * @package Sina42048\LaraPay\Facade
 */
class Lapay extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    public static function getFacadeAccessor()
    {
        return 'larapay';
    }
}