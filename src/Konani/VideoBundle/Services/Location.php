<?php

namespace Konani\VideoBundle\Services;

/**
 * User location
 *
 * Class Location
 * @package Konani\VideoBundle\Services
 */
class Location
{
    private $ip;
    public $found = false;
    public $city;
    public $region;
    public $lat;
    public $lng;

    /**
     * Returns inf about clients location
     *
     * @param $ip
     * @return $this
     */
    public function getMyLocation($ip)
    {
        $this->ip = $ip;
        $details = json_decode(file_get_contents("http://ipinfo.io/" . $this->ip . "/json"));
        if ($details->loc) {
            $this->found = true;
            $latLng = explode(",", $details->loc);
            $this->city = $details->city;
            $this->region = $details->region;
            $this->lat = $latLng[0];
            $this->lng = $latLng[1];
        }
        return $this;
    }
}