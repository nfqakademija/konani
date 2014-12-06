<?php

namespace Konani\FrontendBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Google_Service_YouTube;

class DefaultController extends Controller
{
    public function indexAction()
    {
        //$ip = $this->get('request')->getClientIp();
        $ip = "86.38.9.252";
        $details = json_decode(file_get_contents("http://ipinfo.io/".$ip."/json"));
        if ($details->loc) {
            $city = $details->city;
            $region = $details->region;
            $latLng = explode(",",$details->loc);
            $videos = $this->getDoctrine()->getRepository('KonaniVideoBundle:Video')->findClosestVideos($latLng[0],$latLng[1],9,100);

            $mergedVideos = [];
            if (count($videos) > 0) {
                //$id_string = "";
                $my_client = $this->get('google_client');
                $client = $my_client->getGoogleClient();
                $youtube = new Google_Service_YouTube($client);

                foreach($videos as $video) {
                    //$id_string .= $video->getYoutubeId() . "," ;
                    $searchResponse = $youtube->videos->listVideos('snippet', [
                            'id' => $video->getYoutubeId(),
                        ]);

                    if ($searchResponse['pageInfo']['totalResults']) {
                        $mergedVideos[$video->getId()] = $searchResponse['items'][0];
                    }
                }
            }
            return $this->render('FrontendBundle:Default:index.html.twig',
                [
                    'city' => $city,
                    'region' => $region,
                    'videos' => $mergedVideos,
                ]
            );
        }
        return $this->render('FrontendBundle:Default:index.html.twig');
    }
}
