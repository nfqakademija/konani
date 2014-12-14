<?php

namespace Konani\FrontendBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Google_Service_YouTube;

class DefaultController extends Controller
{
    public function indexAction()
    {
        $ip = $this->get('request')->server->get('HTTP_X_REAL_IP'); //$ip = "85.206.23.13";
        $mergedVideos = [];
        if ($location = $this->get('location')->getMyLocation($ip)) {
            $repositoryVideos = $this->getDoctrine()->getRepository('KonaniVideoBundle:Video')->findClosestVideos($location->getLat(),$location->getLng(),6);
            $my_client = $this->get('google_client');
            $client = $my_client->getGoogleClient();
            $youtube = new Google_Service_YouTube($client);
            if (count($repositoryVideos) > 0) {
                $mergedVideos = $this->get('google_client')->getProvidedVideos($repositoryVideos, $youtube);
            }
            return $this->render('FrontendBundle:Default:index.html.twig',
                [
                    'location' => $location,
                    'videos' => $mergedVideos
                ]
            );
        }
        return $this->render('FrontendBundle:Default:index.html.twig');
    }
}
