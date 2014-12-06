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
    public function listVideosByCoordsAction(Request $request)
    {
        try {
            $videosInMap = $this->getDoctrine()
                ->getRepository('KonaniVideoBundle:Video')
                ->findVideosByCoordinates($request->get('min_lat'), $request->get('max_lat'), $request->get('min_lng'), $request->get('min_lat'));
        } catch (NoResultException $e) {
            throw $this->createNotFoundException(
                'No videos found in given coordinates'
            );
        }
        $response = new JsonResponse();
        $response->setData($this->get('json_helper')->createMarkers($videosInMap));
        return $response;
    }

    public function videoByIdAction(Request $request)
    {
        $id = $request->get('id');
        $videoArray = [
            'id' => $id,
        ];
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
        $videoArray['title'] = $searchResponse['items'][0]['snippet']['title'];
        $videoArray['description'] = $searchResponse['items'][0]['snippet']['description'];
        $videoArray['youtube_id'] = $searchResponse['items'][0]['snippet']['id'];
        $videoArray['thumbnail'] = $searchResponse['items'][0]['snippet']['thumbnails']['default']['url'];
        $response = new JsonResponse();
        $response->setData($videoArray);
        return $response;
    }
}