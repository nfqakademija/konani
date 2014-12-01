<?php

namespace Konani\VideoBundle\Services;

use Symfony\Component\Routing\Router;
use Symfony\Component\HttpFoundation\Session\Session;

use Google_Client;

use Google_Service_Exception;
use Google_Exception;

use Google_Service_YouTube_VideoSnippet;
use Google_Service_YouTube_VideoStatus;
use Google_Service_YouTube_Video;
use Google_Http_MediaFileUpload;


class GoogleClient
{
    protected $parameters;
    private $google_client;
    private $redirect;
    private $session;

    public function __construct($parameters, Router $router, Session $session)
    {
        $this->parameters = $parameters;

        $this->google_client = new Google_Client();

        $this->google_client->setClientId($parameters['client_id']);
        $this->google_client->setClientSecret($parameters['client_secret']);
        $this->google_client->setScopes($parameters['scope']);

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
        $return = array();
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

    /**
     * Create a snipet with title, description, tags and category id
     * Numeric video category. See
     * https://developers.google.com/youtube/v3/docs/videoCategories/list
     * @param $file
     * @return Google_Service_YouTube_VideoSnippet
     */
    public function createSnippet($file)
    {
        $snippet = new Google_Service_YouTube_VideoSnippet();
        $snippet->setTitle($file->GetName());
        $snippet->setDescription("Another description");
        $snippet->setTags(array("Snowboarder", "Symfony", "Google", "Youtube"));
        $snippet->setCategoryId("22");
        return $snippet;
    }

    /**
     * Create a video status with privacy status. Options are "public", "private" and "unlisted".
     * @param $privacy
     * @return Google_Service_YouTube_VideoStatus
     */
    public function createStatus($privacy)
    {
        $status = new Google_Service_YouTube_VideoStatus();
        $status->privacyStatus = $privacy;
        return $status;
    }

    /**
     * Associate the snippet and status objects with a new video resource.
     * @param $snippet
     * @param $status
     * @return Google_Service_YouTube_Video
     */
    public function createVideo($snippet, $status)
    {
        $video = new Google_Service_YouTube_Video();
        $video->setSnippet($snippet);
        $video->setStatus($status);
        return $video;
    }

    /**
     * Uploads video to clients youtube channel
     * @param $file
     * @param $youtube
     * @return array
     */
    public function uploadVideo($file, $youtube)
    {
        $return = array();
        try{
            $videoPath = $file->getAbsolutePath();
            $snippet = $this->createSnippet($file);
            $status = $this->createStatus($this->parameters['privacy']);
            $video = $this->createVideo($snippet, $status);
            // Specify the size of each chunk of data, in bytes. Set a higher value for
            // reliable connection as fewer chunks lead to faster uploads. Set a lower
            // value for better recovery on less reliable connections.
            $chunkSizeBytes = 1 * 1024 * 1024;
            // Setting the defer flag to true tells the client to return a request which can be called
            // with ->execute(); instead of making the API call immediately.
            $this->google_client->setDefer(true);
            // Create a request for the API's videos.insert method to create and upload the video.
            $insertRequest = $youtube->videos->insert("status,snippet", $video);
            // Create a MediaFileUpload object for resumable uploads.
            $media = new Google_Http_MediaFileUpload(
                $this->google_client,
                $insertRequest,
                'video/*',
                null,
                true,
                $chunkSizeBytes
            );
            $media->setFileSize(filesize($videoPath));
            // Read the media file and upload it chunk by chunk.
            $uploadStatus = false;
            $handle = fopen($videoPath, "rb");
            while (!$uploadStatus && !feof($handle)) {
                $chunk = fread($handle, $chunkSizeBytes);
                $uploadStatus = $media->nextChunk($chunk);
            }
            fclose($handle);
            // If you want to make other calls after the file upload, set setDefer back to false
            $this->google_client->setDefer(false);
            $return['video'] = $uploadStatus;

        } catch (Google_Service_Exception $e) {
            $return['errors']['service'] = htmlspecialchars($e->getMessage());
        } catch (Google_Exception $e) {
            $return['errors']['client'] = htmlspecialchars($e->getMessage());
        }
        return $return;
    }

    /**
     * Call the channels.list method to retrieve information about the currently authenticated user's channel.
     *
     * Extract the unique playlist ID that identifies the list of videos uploaded to the channel, and then call the playlistItems.list method to retrieve that list.
     *
     * @param $youtube
     * @return array
     */
    public function getClientVideos($youtube)
    {
        $return = array();
        try {
            $channelsResponse = $youtube->channels->listChannels('contentDetails', array(
                    'mine' => 'true',
                ));
            foreach ($channelsResponse['items'] as $channel) {

                $uploadsListId = $channel['contentDetails']['relatedPlaylists']['uploads'];
                $playlistItemsResponse = $youtube->playlistItems->listPlaylistItems('snippet', array(
                        'playlistId' => $uploadsListId,
                        'maxResults' => 50
                    ));
                foreach ($playlistItemsResponse['items'] as $playlistItem) {
                    $return[$playlistItem['snippet']['resourceId']['videoId']] = $playlistItem['snippet']['title'];
                }
            }
        } catch (Google_Service_Exception $e) {
            $return['errors']['service'] = htmlspecialchars($e->getMessage());
        } catch (Google_Exception $e) {
            $return['errors']['client'] = htmlspecialchars($e->getMessage());
        }
        return $return;
    }
}