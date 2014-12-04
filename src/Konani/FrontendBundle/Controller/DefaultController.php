<?php

namespace Konani\FrontendBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Intl\Exception\InvalidArgumentException;

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
        }
        return $this->render('FrontendBundle:Default:index.html.twig',
            array(
                'city' => isset($city) ? $city : "",
                'region' => isset($region) ? $region : "",
                'videos' => isset($videos) ? $videos : "",
            )
        );
    }

}
