<?php

namespace Konani\VideoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Google_Client;
use Google_Service_YouTube;
use Google_Service_Exception;
use Google_Exception;

class DefaultController extends Controller
{
    public function addVideoAction()
    {
        $OAUTH2_CLIENT_ID = $this->container->getParameter('google.client_id');
        $OAUTH2_CLIENT_SECRET = $this->container->getParameter('google.client_secret');

        $client = new Google_Client();
        $client->setClientId($OAUTH2_CLIENT_ID);
        $client->setClientSecret($OAUTH2_CLIENT_SECRET);
        $client->setScopes('https://www.googleapis.com/auth/youtube');

        $redirect = filter_var('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'],
            FILTER_SANITIZE_URL);
        $client->setRedirectUri($redirect);

        //$youtube = new Google_Service_YouTube($client);

        $code = $this->get('request')->get('code');
        if ($code) {
            if (strval($this->get('session')->get('state')) !== strval($this->get('request')->get('state'))) {
                die('The session state did not match.');
            }

            $client->authenticate($code);
            $this->get('session')->set('token', $client->getAccessToken());
            $this->redirect($redirect);
        }

        if ($this->get('session')->get('token')) {
            $client->setAccessToken($this->get('session')->get('token'));
        }

        $htmlBody = "";

        if ($client->getAccessToken()) {
            try {
                $snippet = new Google_Service_YouTube_VideoSnippet();
            } catch (Google_Service_Exception $e) {
                $htmlBody .= sprintf('<p>A service error occurred: <code>%s</code></p>',
                    htmlspecialchars($e->getMessage()));
            } catch (Google_Exception $e) {
                $htmlBody .= sprintf('<p>An client error occurred: <code>%s</code></p>',
                    htmlspecialchars($e->getMessage()));
            }
        } else {
            $state = mt_rand();
            $client->setState($state);
            $this->get('session')->set('state', $state);

            $authUrl = $client->createAuthUrl();
            $htmlBody .= "<h3>Authorization Required</h3>
              <p>You need to <a href='".$authUrl."'>authorize access</a> before proceeding.<p>";

        }

        return $this->render('KonaniVideoBundle:Default:addVideo.html.twig', array( 'html' => $htmlBody));
    }
}
