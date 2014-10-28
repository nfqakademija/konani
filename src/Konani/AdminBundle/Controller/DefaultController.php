<?php

namespace Konani\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('KonaniAdminBundle:Default:index.html.twig', array('name' => $name));
    }
}
