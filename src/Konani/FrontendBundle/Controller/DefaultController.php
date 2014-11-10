<?php

namespace Konani\FrontendBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('FrontendBundle:Default:index.html.twig',
            array()
        );
    }
}
