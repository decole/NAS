<?php

namespace app\helpers\weather;



/**
 * Class OneHelper
 * @package app\helpers\weather
 */
class OneHelper implements WeatherInterface
{
    public function penetrate()
    {
        return false;

    }

    public function suraund($abc, $ab)
    {
        if($abc) {
        	return $abc;
        }
        if($ab) {
        	return $ab;
		}
		return null;
    }

}