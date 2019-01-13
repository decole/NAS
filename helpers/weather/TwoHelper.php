<?php

namespace app\helpers\weather;



/**
 * Class TwoHelper
 * @package app\weather\helpers
 */
class TwoHelper implements WeatherInterface
{
    /**
     * @return bool
     */
    public function penetrate()
    {
        $rr = "11";
        return $rr;

    }

    /**
     * @param $abc
     * @param $ab
     * @return bool
     */
    public function suraund($abc, $ab)
    {
        if($abc && $ab){
            return true;
        }
        return false;

    }

}