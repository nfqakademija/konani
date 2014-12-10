<?php

namespace Konani\VideoBundle\Services;

/**
 * Generates arrays for JSON
 *
 * Class JsonHelper
 * @package Konani\VideoBundle\Services
 */
class JsonHelper
{
    protected $parameters;

    /**
     * Returns video geotags array for map
     *
     * @param $videos
     * @return array
     */
    public function createMarkers($videos)
    {
        $videosArray = array();
        foreach($videos as $video) {
            array_push($videosArray, array(
                    'id'  => $video->getId(),
                    'lat' => $video->getLatitude(),
                    'lng' => $video->getLongitude(),
                ));
        }
        return $videosArray;
    }
}