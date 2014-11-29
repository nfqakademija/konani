<?php

namespace Konani\VideoBundle\Services;

use Google_Client;
use Symfony\Component\Routing\Router;

class GoogleClient
{
    protected $parameters;
    private $google_client;
    private $redirect;

    public function __construct($parameters, Router $router)
    {
        $this->google_client = new Google_Client();

        $this->google_client->setClientId($parameters['google.client_id']);
        $this->google_client->setClientSecret($parameters['google.client_secret']);
        $this->google_client->setScopes($parameters['google.scope']);

        $redirect = filter_var('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'],
            FILTER_SANITIZE_URL);

        $this->setRedirect($redirect);

        $this->google_client->setRedirectUri($redirect);
    }

    public function setRedirect($redirect)
    {
        $this->redirect = $redirect;

        return $this;
    }

    public function getRedirect()
    {
        return $this->redirect;
    }

    public function getGoogleClient()
    {
        return $this->google_client;
    }

}