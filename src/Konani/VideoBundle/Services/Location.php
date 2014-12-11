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
    private $found = false;
    private $city;
    private $region;
    private $country;
    private $lat;
    private $lng;

    /**
     * Returns inf about clients location
     *
     * @param $ip
     * @return $this
     */
    public function getMyLocation($ip)
    {
        $this->setIp($ip);
        $details = json_decode(file_get_contents("http://ipinfo.io/" . $this->getIp() . "/json"));
        if ($details->loc) {
            $this->setFound(true);
            $latLng = explode(",", $details->loc);
            $this->setCity($details->city);
            $this->setRegion($details->region);
            $this->setCountry($details->country);
            $this->setLat($latLng[0]);
            $this->setLng($latLng[1]);
        }
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param mixed $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param mixed $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * @return boolean
     */
    public function isFound()
    {
        return $this->found;
    }

    /**
     * @param boolean $found
     */
    public function setFound($found)
    {
        $this->found = $found;
    }

    /**
     * @return mixed
     */
    public function getLat()
    {
        return $this->lat;
    }

    /**
     * @param mixed $lat
     */
    public function setLat($lat)
    {
        $this->lat = $lat;
    }

    /**
     * @return mixed
     */
    public function getLng()
    {
        return $this->lng;
    }

    /**
     * @param mixed $lng
     */
    public function setLng($lng)
    {
        $this->lng = $lng;
    }

    /**
     * @return mixed
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * @param mixed $region
     */
    public function setRegion($region)
    {
        $this->region = $region;
    }

    /**
     * @return mixed
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param mixed $ip
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }
}