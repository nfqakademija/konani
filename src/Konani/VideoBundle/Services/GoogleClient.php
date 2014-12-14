<?php

namespace Konani\VideoBundle\Services;

use Symfony\Component\Routing\Router;
use Symfony\Component\HttpFoundation\Session\Session;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Konani\VideoBundle\Services\ArraySearch;

use Google_Client;

use Google_Service_YouTube_VideoSnippet;
use Google_Service_YouTube_VideoStatus;
use Google_Service_YouTube_Video;
use Google_Http_MediaFileUpload;


/**
 * Class for requests and responses to/from Google API V3
 *
 * Class GoogleClient
 * @package Konani\VideoBundle\Services
 */
class GoogleClient
{
    protected $parameters;
    protected $session;
    protected $google_client;
    protected $jsonEncoder;
    protected $arraySearch;

    public function __construct($parameters, Router $router, Session $session, JsonEncoder $jsonEncoder, ArraySearch $arraySearch)
    {
        $this->parameters = $parameters;
        $this->session = $session;
        $this->jsonEncoder = $jsonEncoder;
        $this->arraySearch = $arraySearch;

        $this->google_client = new Google_Client();
        $this->google_client->setClientId($parameters['client_id']);
        $this->google_client->setClientSecret($parameters['client_secret']);
        $this->google_client->setDeveloperKey($parameters['api_key']);
        $this->google_client->setScopes($parameters['scope']);
        $this->google_client->setRedirectUri($router->generate('video_authenticate_google', array(), true));
        //$this->google_client->refreshToken("test_token");
    }

    /**
     * Checks if state from request is equal to state in the session, authenticates with google and saves access token to session
     *
     * @param $code
     * @param $state
     */
    public function authenticateToken($code, $state)
    {
        if (strval($this->session->get('state')) !== strval($state)) {
            die('The session state did not match.');
        }
        $this->google_client->authenticate($code);
        $this->session->set('token', $this->google_client->getAccessToken());
    }

    /**
     * Resets google access token
     */
    public function resetToken()
    {
        if ($this->session->get('token')) {
            $this->google_client->setAccessToken($this->session->get('token'));
        }
    }

    /**
     * Returns an authentication with google URL
     *
     * @return string
     */
    public function getAuthUrl()
    {
        $state = mt_rand();
        $this->google_client->setState($state);
        $this->session->set('state', $state);

        return $this->google_client->createAuthUrl();
    }

    /**
     * Determines if client has a linked youtube channel
     *
     * @param $youtube
     * @return bool
     */
    public function getChannelLinked($youtube)
    {
        $channelsResponse = $youtube->channels->listChannels(
            'status',
            array(
                'mine' => 'true',
            )
        );

        if ($channelsResponse['items'][0]['status']->getIsLinked(
            ) && $channelsResponse['items'][0]['status']->getPrivacyStatus() == 'public'
        ) {
            return true;
        }

        return false;
    }

    /**
     * @return Google_Client
     */
    public function getGoogleClient()
    {
        return $this->google_client;
    }

    /**
     * Create a snippet with title, description, tags and category id
     *
     * @param $file
     * @return Google_Service_YouTube_VideoSnippet
     */
    public function createSnippet($file)
    {
        $snippet = new Google_Service_YouTube_VideoSnippet();
        $snippet->setTitle($file->getName());
        $snippet->setDescription($file->getDescription());
        $snippet->setTags($this->stringTagsToArray($file->getTags()));
        $snippet->setCategoryId($file->getCategory());
        return $snippet;
    }

    /**
     * Converts tags from string to array without keys
     *
     * @param $tags
     * @return array
     */
    private function stringTagsToArray($tags)
    {
        $tagsArray = explode(",",$tags);
        return $tagsArray;
    }

    /**
     * Create a video status with privacy status. Options are "public", "private" and "unlisted".
     *
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
     *
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
     *
     * @param $file
     * @param $youtube
     * @return array
     */
    public function uploadVideo($file, $youtube)
    {
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

        return $uploadStatus;
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
        $channelsResponse = $youtube->channels->listChannels('contentDetails',['mine' => 'true']);
        foreach ($channelsResponse['items'] as $channel) {
            $uploadsListId = $channel['contentDetails']['relatedPlaylists']['uploads'];
            $playlistItemsResponse = $youtube->playlistItems->listPlaylistItems('snippet, status',['playlistId' => $uploadsListId,'maxResults' => 50]);
            foreach ($playlistItemsResponse['items'] as $playlistItem) {
                if ($playlistItem['status']['privacyStatus'] == 'public') {
                    $return[$playlistItem['snippet']['resourceId']['videoId']] = $playlistItem['snippet']['title'];
                }
            }
        }
        return $return;
    }

    /**
     * Returns an array of youtube video categories for choice form fields
     *
     * @param $youtube
     * @return array
     */
    public function getVideoCategories($youtube)
    {
        $return = array();
        $videoCategories = $youtube->videoCategories->listVideoCategories('snippet', ['regionCode' => 'US']);
        foreach ($videoCategories['items'] as $category) {
            if ($category['snippet']['assignable']) {
                $return[$category['id']] = $category['snippet']['title'];
            }
        }
        return $return;
    }

    /**
     * Returns information about provided videos array from youtube
     *
     * @param $repositoryVideos
     * @param $youtube
     * @return array
     */
    public function getProvidedVideos($repositoryVideos, $youtube)
    {
        $searchResponse = $youtube->videos->listVideos(
            'snippet',
            [
                'id' => $this->getVideoIdString($repositoryVideos),
                'maxResults' => 6,
            ]
        );
        //print_r($searchResponse['items']);
        $mergedVideos = $this->mergeVideos($repositoryVideos, $searchResponse);
        return $mergedVideos;
    }

    /**
     * Merges video data from repository with video data from Youtube API
     *
     * @param $repositoryVideos
     * @param $searchResponse
     * @return array
     */
    private function mergeVideos($repositoryVideos, $searchResponse)
    {
        $mergedVideos = [];
        foreach ($repositoryVideos as $repositoryVideo) {
            $searchKey = $this->arraySearch->search($searchResponse['items'], 'id', $repositoryVideo->getYoutubeId());
            if ($searchKey !== false) {
                $mergedVideos[$repositoryVideo->getId()]['youtube'] = $searchResponse['items'][$searchKey];
            } else {
                $mergedVideos[$repositoryVideo->getId()]['youtube'] = $this->createNotFoundThumbnail();
            }
            $mergedVideos[$repositoryVideo->getId()]['location']['lat'] = $repositoryVideo->getLatitude();
            $mergedVideos[$repositoryVideo->getId()]['location']['lng'] = $repositoryVideo->getLongitude();
        }
        return $mergedVideos;
    }

    /**
     * Returns a not found thumbnail array
     *
     * @return array
     */
    private function createNotFoundThumbnail()
    {
        return array(
          'snippet' => array(
              'title' => 'Video not found',
              'thumbnails' => array (
                  'medium' => array (
                      'url' => 'http://i.ytimg.com/vi/QfgoDDh4kE0/maxresdefault.jpg',
                      'height' => '180',
                      'width' => '320'
                  )
              )
          )
        );
    }

    /**
     * Returns information about place closest to provided coordinates
     *
     * @param $lat
     * @param $lng
     * @return array|null
     */
    public function getNearbyPlace($lat, $lng)
    {
        $json = sprintf(
            "https://maps.googleapis.com/maps/api/place/nearbysearch/json?location=%s,%s&rankby=distance&minprice=0&maxprice=0&types=%s&key=%s",
            $lat,
            $lng,
            $this->getPlacesTypes(),
            $this->parameters['api_key']
        );
        $places = $this->jsonEncoder->decode(file_get_contents($json), 'json');
        if (count($places['results']) > 0) {

            return array('name' => $places['results'][0]['name'], 'address' => $places['results'][0]['vicinity']);
        }
        return null;
    }

    /**
     * Returns comma separated list of youtube video ID's
     *
     * @param $repositoryVideos
     * @return string
     */
    private function getVideoIdString($repositoryVideos)
    {
        $videosIdString = "";
        foreach ($repositoryVideos as $repositoryVideo) {
            $videosIdString .= $repositoryVideo->getYoutubeId().",";
        }
        return $videosIdString;
    }

    /**
     * Returns filter value for places to search
     *
     * @return string
     */
    private function getPlacesTypes()
    {
        $types = "airport|amusement_park|aquarium|art_gallery|bakery|bank|bar|beauty_salon|bicycle_store|book_store|bowling_alley|bus_station|cafe|campground|car_dealer|car_rental|car_repair|car_wash|casino|cemetery|church|city_hall|clothing_store|convenience_store|courthouse|dentist|department_store|doctor|electrician|electronics_store|embassy|establishment|finance|fire_station|florist|food|funeral_home|furniture_store|gas_station|general_contractor|grocery_or_supermarket|gym|hair_care|hardware_store|health|hindu_temple|home_goods_store|hospital|insurance_agency|jewelry_store|laundry|lawyer|library|liquor_store|local_government_office|locksmith|lodging|meal_delivery|meal_takeaway|mosque|movie_rental|movie_theater|moving_company|museum|night_club|painter|park|parking|pet_store|pharmacy|physiotherapist|place_of_worship|plumber|police|post_office|real_estate_agency|restaurant|roofing_contractor|rv_park|school|shoe_store|shopping_mall|spa|stadium|storage|store|subway_station|synagogue|taxi_stand|train_station|travel_agency|university|veterinary_care|zoo";
        return $types;
    }
}