<?php

namespace Konani\VideoBundle\Controller;

use Doctrine\ORM\NoResultException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Google_Service_YouTube;
use Google_Service_Exception;
use Google_Exception;

use Google_Service_YouTube_VideoSnippet;
use Google_Service_YouTube_VideoStatus;
use Google_Service_YouTube_Video;
use Google_Http_MediaFileUpload;

use Konani\VideoBundle\Entity\File;

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
    public function uploadAction(Request $request)
    {
        $file = new File();

        $form = $this->createFormBuilder($file)
            ->add('name')
            ->add('file')
            ->add('save','submit')
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $file->setUser($this->getUser());

            $em->persist($file);
            $em->flush();

            return $this->redirect($this->generateUrl('video_uploaded'));
        }

        return $this->render('KonaniVideoBundle:Default:upload.html.twig', array(
                'form' => $form->createView(),
            ));
    }

    /**
     * Lists all user uploaded locally videos
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function uploadedAction()
    {
        $user = $this->getUser();

        $videos = $this->getDoctrine()
            ->getRepository('KonaniVideoBundle:File')
            ->findBy(array("user" => $user));

        return $this->render('KonaniVideoBundle:Default:uploaded.html.twig', array(
                'videos' => $videos
            ));
    }

    public function deleteUploadedAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        try {
            $file = $this->getDoctrine()
                ->getRepository('KonaniVideoBundle:File')
                ->getOneByIdAndUserId($id, $this->getUser()->getId());
        } catch (NoResultException $e) {
            throw $this->createNotFoundException(
                'No video found for id '.$id
            );
        }

        $em->remove($file);
        $em->flush();

        return $this->redirect($this->generateUrl('video_uploaded'));
    }

    public function authenticateGoogleAction()
    {
        $my_client = $this->get('google_client');
        $client = $my_client->getGoogleClient();

        $code = $this->get('request')->get('code');
        if ($code) {
            if (strval($this->get('session')->get('state')) !== strval($this->get('request')->get('state'))) {
                die('The session state did not match.');
            }

            $client->authenticate($code);
            $this->get('session')->set('token', $client->getAccessToken());
            $this->redirect($my_client->getRedirect());
        }

        if ($this->get('session')->get('token')) {
            $client->setAccessToken($this->get('session')->get('token'));
        }

        if ($client->getAccessToken()) {
            return $this->render('KonaniVideoBundle:Default:authenticateGoogle.html.twig');
        } else {
            $state = mt_rand();
            $client->setState($state);
            $this->get('session')->set('state', $state);

            $authUrl = $client->createAuthUrl();

            return $this->render('KonaniVideoBundle:Default:authenticateGoogle.html.twig', array(
                    'authUrl' => $authUrl,
                ));
        }


    }
    public function uploadToYoutubeAction($id)
    {
        $my_client = $this->get('google_client');
        $client = $my_client->getGoogleClient();

        $youtube = new Google_Service_YouTube($client);

        if ($this->get('session')->get('token')) {
            $client->setAccessToken($this->get('session')->get('token'));
        }

        $return = array();

        if ($client->getAccessToken()) {
            try{
                $file = $this->getDoctrine()
                    ->getRepository('KonaniVideoBundle:File')
                    ->find($id);

                if (!$file) {
                    throw $this->createNotFoundException(
                        'No video found for id '.$id
                    );
                }

                $videoPath = $file->getAbsolutePath();

                // Create a snipet with title, description, tags and category id
                $snippet = new Google_Service_YouTube_VideoSnippet();
                $snippet->setTitle($file->GetName());
                $snippet->setDescription("Another description");
                $snippet->setTags(array("Snowboarder", "Symfony", "Google", "Youtube"));

                // Numeric video category. See
                // https://developers.google.com/youtube/v3/docs/videoCategories/list
                $snippet->setCategoryId("22");

                // Create a video status with privacy status. Options are "public", "private" and "unlisted".
                $status = new Google_Service_YouTube_VideoStatus();
                $status->privacyStatus = "private";
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

                $return['video'] = $status;

            // Reikia pirma useriui funkcijos - susikurti youtube cahnneli, paskuj uploadinti

            } catch (Google_Service_Exception $e) {
                $return['errors']['service'] = htmlspecialchars($e->getMessage());
            } catch (Google_Exception $e) {
                $return['errors']['client'] = htmlspecialchars($e->getMessage());
            }

            $this->get('session')->set('token', $client->getAccessToken());

        } else {
            return $this->redirect($this->generateUrl('video_authenticate_google'));
        }

        return $this->render('KonaniVideoBundle:Default:uploadToYoutube.html.twig', array( 'params' => $return));
    }
}
