<?php

namespace Konani\MapBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

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
        $videos = $request->request->get('videos');

        $repository = $this->getDoctrine()
            ->getRepository('KonaniVideoBundle:Video');
        $videos = $repository->findById($videos);

        return $this->render('KonaniMapBundle:Default:videos.html.twig',array('videos'=>$videos));
    }

}
