<?php

namespace Konani\VideoBundle\Services;

use Symfony\Component\Routing\Router;
use Symfony\Component\HttpFoundation\Session\Session;

use Google_Client;

use Google_Service_Exception;
use Google_Exception;

use Google_Service_YouTube_VideoSnippet;

class GoogleClient
{
    protected $parameters;
    private $google_client;
    private $redirect;
    private $session;

    public function __construct($parameters, Router $router, Session $session)
    {
        $this->google_client = new Google_Client();

        $this->google_client->setClientId($parameters['google.client_id']);
        $this->google_client->setClientSecret($parameters['google.client_secret']);
        $this->google_client->setScopes($parameters['google.scope']);

        $this->setRedirect($router->generate('video_authenticate_google', array(), true));

        $this->google_client->setRedirectUri($this->getRedirect());

        $this->session = $session;
    }
    public function resetToken()
    {
        if ($this->session->get('token')) {
            $this->google_client->setAccessToken($this->session->get('token'));
        }
    }
    public function getAuthUrl()
    {
        $state = mt_rand();
        $this->google_client->setState($state);
        $this->session->set('state', $state);

        return $this->google_client->createAuthUrl();
    }
    public function getChannelStatus($youtube)
    {
        try {
            $channelsResponse = $youtube->channels->listChannels('status', array(
                    'mine' => 'true',
                ));

            $return['channelLinked'] = $channelsResponse['items'][0]['status']->getIsLinked();
            $return['channelPrivacy'] = $channelsResponse['items'][0]['status']->getPrivacyStatus();

        } catch (Google_Service_Exception $e) {
            $return['errors']['service'] = htmlspecialchars($e->getMessage());
        } catch (Google_Exception $e) {
            $return['errors']['client'] = htmlspecialchars($e->getMessage());
        }
        return $return;
    }
    public function createSnippet($file)
    {
        // Create a snipet with title, description, tags and category id
        $snippet = new Google_Service_YouTube_VideoSnippet();
        $snippet->setTitle($file->GetName());
        $snippet->setDescription("Another description");
        $snippet->setTags(array("Snowboarder", "Symfony", "Google", "Youtube"));

        // Numeric video category. See
        // https://developers.google.com/youtube/v3/docs/videoCategories/list
        $snippet->setCategoryId("22");
        return $snippet;
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
    public function channelStatusOK($youtube)
    {
        $channelsResponse = $youtube->channels->listChannels(
            'status',
            array(
                'mine' => 'true',
            )
        );

        if ($channelsResponse['items'][0]['status']->getIsLinked() && $channelsResponse['items'][0]['status']->getPrivacyStatus() == 'public') {
            return true;
        }

        return false;
    }
}