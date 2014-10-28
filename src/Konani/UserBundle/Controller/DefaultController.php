<?php

namespace Konani\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('KonaniUserBundle:Default:index.html.twig', array('name' => $name));
    }
}
