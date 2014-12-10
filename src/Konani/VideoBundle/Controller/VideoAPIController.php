<?php

namespace Konani\VideoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Doctrine\ORM\NoResultException;
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\HttpFoundation\Request;

use Google_Service_YouTube;


/**
 * Class handles requests from map and returns formatted responses
 *
 * Class VideoAPIController
 * @package Konani\VideoBundle\Controller
 */
class VideoAPIController extends Controller
{
    public function listVideosByCoordsAction($min_lat,$max_lat,$min_lng,$max_lng)
    {
        try {
            $videosInMap = $this->getDoctrine()
                ->getRepository('KonaniVideoBundle:Video')
                ->findVideosByCoordinates($min_lat, $max_lat, $min_lng, $max_lng);
        } catch (NoResultException $e) {
            throw $this->createNotFoundException(
                'No videos found in given coordinates'
            );
        }
        $response = new JsonResponse();
        $response->setData($this->get('json_helper')->createMarkers($videosInMap));
        return $response;
    }

    /**
     * Returns information about specific video to map's infowindow
     *
     * @param $id
     * @return JsonResponse
     * @throws \Exception
     */
    public function videoByIdAction($id)
    {
        $my_client = $this->get('google_client');
        $client = $my_client->getGoogleClient();
        $youtube = new Google_Service_YouTube($client);
        try {
            $video = $this->getDoctrine()
                ->getRepository('KonaniVideoBundle:Video')
                ->findOneBy([
                        'id' => $id
                    ]);
            $searchResponse = $youtube->videos->listVideos('snippet', [
                    'id' => $video->getYoutubeId(),
                ]);
        } catch (NoResultException $e) {
            throw $this->createNotFoundException(
                'No video found for id '.$id
            );
        }
        $response = new JsonResponse();
        $response->setData($this->videoArrayFromResponseAction($id, $searchResponse['items'][0]['snippet']));
        return $response;
    }

    /**
     * Returns name and address of the closest google place to the specified coordinates
     *
     * @param $lat
     * @param $lng
     * @return JsonResponse
     * @throws \Exception
     */
    public function nearbyPlaceAction($lat,$lng)
    {
        $nearby_place = $this->get('google_client')->getNearbyPlace($lat,$lng);
        $response = new JsonResponse();
        $response->setData($nearby_place);
        return $response;
    }

    /**
     * Forms an cleaner array of a video data
     *
     * @param $id
     * @param $searchResponse
     * @return array
     */
    private function videoArrayFromResponseAction($id,$searchResponse)
    {
        $videoArray = [
            'id' => $id,
            'title' => $searchResponse['title'],
            'description' => $searchResponse['description'],
            'youtube_id' => $searchResponse['id'],
            'thumbnail' => [
                'url' => $searchResponse['thumbnails']['default']['url'],
                'width' => $searchResponse['thumbnails']['default']['width'],
                'height' => $searchResponse['thumbnails']['default']['height'],
            ]
        ];
        return $videoArray;
    }
}