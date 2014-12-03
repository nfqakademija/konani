<?php

namespace Konani\VideoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Doctrine\ORM\NoResultException;
use Symfony\Component\HttpFoundation\JsonResponse;

use Google_Service_YouTube;


/**
 * Class handles requests from map and returns formatted responses
 *
 * Class VideoAPIController
 * @package Konani\VideoBundle\Controller
 */
class VideoAPIController extends Controller
{
    public function listVideosByCoordsAction($min_lat, $max_lat, $min_lng, $max_lng)
    {
        try {
            $videos = $this->getDoctrine()
                ->getRepository('KonaniVideoBundle:Video')
                ->findVideosByCoordinates($min_lat, $max_lat, $min_lng, $max_lng);
            $videosArray = array();
            foreach($videos as $video) {
                array_push($videosArray, array(
                        'id'  => $video->getId(),
                        'lat' => $video->getLatitude(),
                        'lng' => $video->getLongitude(),
                    ));
            }

        } catch (NoResultException $e) {
            throw $this->createNotFoundException(
                'No videos found in given coordinates'
            );
        }

        $response = new JsonResponse();
        $response->setData($videosArray);

        return $response;
    }

    public function videoByIdAction($id)
    {
        $videoArray = array(
            'id' => $id,
        );
        $my_client = $this->get('google_client');
        $client = $my_client->getGoogleClient();
        $youtube = new Google_Service_YouTube($client);
        try {
            $video = $this->getDoctrine()
                ->getRepository('KonaniVideoBundle:Video')
                ->findOneBy(array(
                        'id' => $id
                    ));
            $searchResponse = $youtube->videos->listVideos('snippet', array(
                    'id' => $video->getYoutubeId(),
                ));
        } catch (NoResultException $e) {
            throw $this->createNotFoundException(
                'No video found for id '.$id
            );
        }
        $videoArray['title'] = $searchResponse['items'][0]['snippet']['title'];
        $videoArray['description'] = $searchResponse['items'][0]['snippet']['description'];
        $videoArray['youtube_id'] = $searchResponse['items'][0]['snippet']['id'];
        $videoArray['thumbnail'] = $searchResponse['items'][0]['snippet']['thumbnails']['default']['url'];

        $response = new JsonResponse();
        $response->setData($videoArray);

        return $response;
    }
}