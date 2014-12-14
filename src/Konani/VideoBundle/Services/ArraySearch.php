<?php

namespace Konani\VideoBundle\Services;

/**
 * Searches for a value of specified field and returns it's key if found or false if not found
 *
 * Class JsonHelper
 * @package Konani\VideoBundle\Services
 */
class ArraySearch
{
     /**
     * Returns key if found
     *
     * @param $videos
     * @param $field
     * @param $value
     * @return array
     */
    public function search($videos, $field, $value)
    {
        foreach($videos as $key => $video) {
            if ( $video[$field] === $value ) {
                return $key;
            }
        }
        return false;
    }
}