<?php

namespace Konani\VideoBundle\Services;

class JsonHelper
{
    protected $parameters;

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