<?php

namespace Konani\VideoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Google_Client;
use Google_Service_YouTube;
use Google_Service_Exception;
use Google_Exception;

use Google_Service_YouTube_VideoSnippet;
use Google_Service_YouTube_VideoStatus;
use Google_Service_YouTube_Video;
use Google_Http_MediaFileUpload;

use Konani\VideoBundle\Form\Type\VideoType;

use Symfony\Component\HttpFoundation\Request;


/**
 * Controls videos actions - add new, edit, delete, upload, upload to youtube...
 *
 * Class VideoController
 * @package Konani\VideoBundle\Controller
 */
class VideoController extends Controller
{
    /**
     * New video upload form
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request)
    {
        $form = $this->createForm(new VideoType());

        $form->handleRequest($request);

        if ($form->isValid()) {

        }

        return $this->render('KonaniVideoBundle:Default:addVideo.html.twig', array(
                'form' => $form->createView(),
            ));
    }

    public function uploadToYoutubeAction()
    {
        $OAUTH2_CLIENT_ID = $this->container->getParameter('google.client_id');
        $OAUTH2_CLIENT_SECRET = $this->container->getParameter('google.client_secret');

        $client = new Google_Client();

        $client->setClientId($OAUTH2_CLIENT_ID);
        $client->setClientSecret($OAUTH2_CLIENT_SECRET);
        $client->setScopes('https://www.googleapis.com/auth/youtube');
        //https://accounts.google.com/o/oauth2/auth


        $redirect = filter_var('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'],
            FILTER_SANITIZE_URL);
        $client->setRedirectUri($redirect);

        $youtube = new Google_Service_YouTube($client);

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
            try{
                // REPLACE with the path to your file that you want to upload
                $videoPath = __DIR__.'/../../../../web/uploads/snbd.mp4';

                // Create a snipet with title, description, tags and category id
                $snippet = new Google_Service_YouTube_VideoSnippet();
                $snippet->setTitle("Programmer in the room");
                $snippet->setDescription("Test description");
                $snippet->setTags(array("Programmer", "Symfony", "Google", "Youtube"));

                // Numeric video category. See
                // https://developers.google.com/youtube/v3/docs/videoCategories/list
                $snippet->setCategoryId("22");

                // Create a video status with privacy status. Options are "public", "private" and "unlisted".
                $status = new Google_Service_YouTube_VideoStatus();
                $status->privacyStatus = "public";
                // Associate the snippet and status objects with a new video resource.
                $video = new Google_Service_YouTube_Video();
                $video->setSnippet($snippet);
                $video->setStatus($status);
                // Specify the size of each chunk of data, in bytes. Set a higher value for
                // reliable connection as fewer chunks lead to faster uploads. Set a lower
                // value for better recovery on less reliable connections.
                $chunkSizeBytes = 1 * 1024 * 1024;
                // Setting the defer flag to true tells the client to return a request which can be called
                // with ->execute(); instead of making the API call immediately.
                $client->setDefer(true);
                // Create a request for the API's videos.insert method to create and upload the video.
                $insertRequest = $youtube->videos->insert("status,snippet", $video);
                // Create a MediaFileUpload object for resumable uploads.
                $media = new Google_Http_MediaFileUpload(
                    $client,
                    $insertRequest,
                    'video/*',
                    null,
                    true,
                    $chunkSizeBytes
                );
                $media->setFileSize(filesize($videoPath));
                // Read the media file and upload it chunk by chunk.
                $status = false;
                $handle = fopen($videoPath, "rb");
                while (!$status && !feof($handle)) {
                    $chunk = fread($handle, $chunkSizeBytes);
                    $status = $media->nextChunk($chunk);
                }
                fclose($handle);
                // If you want to make other calls after the file upload, set setDefer back to false
                $client->setDefer(false);
                $htmlBody .= "<h3>Video Uploaded</h3><ul>";
                $htmlBody .= sprintf('<li>%s (%s)</li>',
                    $status['snippet']['title'],
                    $status['id']);
                $htmlBody .= '</ul>';

            // Reikia pirma useriui funkcijos - susikurti youtube cahnneli, paskuj uploadinti

            } catch (Google_Service_Exception $e) {
                $htmlBody .= sprintf('<p>A service error occurred: <code>%s</code></p>',
                    htmlspecialchars($e->getMessage()));
            } catch (Google_Exception $e) {
                $htmlBody .= sprintf('<p>An client error occurred: <code>%s</code></p>',
                    htmlspecialchars($e->getMessage()));
            }

            $_SESSION['token'] = $client->getAccessToken();
        } else {
            $state = mt_rand();
            $client->setState($state);
            $this->get('session')->set('state', $state);

            $authUrl = $client->createAuthUrl();
            $htmlBody = "
              <h3>Authorization Required</h3>
              <p>You need to <a href='".$authUrl."'>authorize access</a> before proceeding.<p>";
        }

        return $this->render('KonaniVideoBundle:Default:uploadVideo.html.twig', array( 'html' => $htmlBody));
    }
}
