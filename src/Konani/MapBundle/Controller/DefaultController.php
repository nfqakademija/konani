<?php

namespace Konani\MapBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Google_Service_YouTube;
use Symfony\Component\HttpFoundation\JsonResponse;

class DefaultController extends Controller
{
    public function indexAction()
    {
        $repository = $this->getDoctrine()
            ->getRepository('KonaniVideoBundle:Video');
        $videos = $repository->findAll();

        $request = Request::createFromGlobals();
        $location = $request->request->get('location');
        $latitude = $request->request->get('latitude');
        $longitude = $request->request->get('longitude');
        return $this->render('KonaniMapBundle:Default:index.html.twig',array(
            'location' => $location,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'videos' => $videos
        ));
    }

    public function getVideosAction() {

        $request = Request::createFromGlobals();
        $ids = $request->request->get('videos');

        $result = array();
        foreach ($ids as $video) {
            $result[] = $this->getVideo($video);
        }

        return $this->render('KonaniMapBundle:Default:videos.html.twig',array('videos'=>$result));
    }

    public function getVideo($id) {
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
        $videoArray['title'] = $searchResponse['modelData']['items'][0]['snippet']['title'];
        $videoArray['description'] = $searchResponse['modelData']['items'][0]['snippet']['description'];
        $videoArray['youtube_id'] = $searchResponse['modelData']['items'][0]['id'];
        $videoArray['thumbnail'] = $searchResponse['modelData']['items'][0]['snippet']['thumbnails']['default']['url'];
        return $videoArray;
    }

}
