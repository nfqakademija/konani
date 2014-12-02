<?php

namespace Konani\VideoBundle\Controller;

use Doctrine\ORM\NoResultException;
use Konani\VideoBundle\Entity\Video;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Google_Service_YouTube;

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

    public function taggedAction()
    {
        $user = $this->getUser();

        $videos = $this->getDoctrine()
            ->getRepository('KonaniVideoBundle:Video')
            ->findBy(array("user" => $user));

        return $this->render('KonaniVideoBundle:Default:tagged.html.twig', array(
                'videos' => $videos
            ));
    }

    /**
     * Deletes video entity if exists
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
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

    /**
     * Authenticates client and redirects if gets right parametes / otherwise provides authentication link
     * @return \Symfony\Component\HttpFoundation\Response
     */
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
            return $this->redirect($this->generateUrl('video_authenticate_google'));
        }

        $my_client->resetToken();

        $return = array();

        if ($client->getAccessToken() && !$client->isAccessTokenExpired()) {
            $youtube = new Google_Service_YouTube($client);
            $return = $my_client->getChannelStatus($youtube);

            $this->get('session')->set('token', $client->getAccessToken());

            return $this->render('KonaniVideoBundle:Default:authenticateGoogle.html.twig', array(
                'params' => $return,
            ));
        } else {
            $return['authUrl'] = $my_client->getAuthUrl();
            return $this->render('KonaniVideoBundle:Default:authenticateGoogle.html.twig', array(
                    'params' => $return,
                ));
        }
    }
    /*public function createYoutubeChannelAction()
    {
        $my_client = $this->get('google_client');
        $client = $my_client->getGoogleClient();
        //$youtube = new Google_Service_YouTube($client);
        $my_client->resetToken();
        $return = array();
        if ($client->getAccessToken() && !$client->isAccessTokenExpired()) {
            //$return = updateChannelPrivacyAction($client);
            $this->get('session')->set('token', $client->getAccessToken());
            return $this->render('KonaniVideoBundle:Default:createYoutubeChannel.html.twig', array(
                    'params' => $return,
                ));
        } else {
            return $this->redirect($this->generateUrl('video_authenticate_google'));
        }
    }*/

    /**
     * Uploads video to clients youtube channel
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function uploadToYoutubeAction($id)
    {
        $my_client = $this->get('google_client');
        $client = $my_client->getGoogleClient();

        $youtube = new Google_Service_YouTube($client);
        $my_client->resetToken();

        $file = $this->getDoctrine()
            ->getRepository('KonaniVideoBundle:File')
            ->find($id);

        if (!$file) {
            throw $this->createNotFoundException(
                'No video found for id ' . $id
            );
        }
        if (!$client->getAccessToken() || $client->isAccessTokenExpired()) {
            return $this->redirect($this->generateUrl('video_authenticate_google'));
        }
        if ($my_client->channelStatusOK($youtube)) {
            $return = $my_client->uploadVideo($file, $youtube);
        } else {
            return $this->redirect($this->generateUrl('video_authenticate_google'));
        }
        $this->get('session')->set('token', $client->getAccessToken());

        return $this->render('KonaniVideoBundle:Default:uploadToYoutube.html.twig', array( 'params' => $return));
    }

    public function newTagAction(Request $request)
    {
        $my_client = $this->get('google_client');
        $client = $my_client->getGoogleClient();
        $youtube = new Google_Service_YouTube($client);
        $my_client->resetToken();

        if (!$client->getAccessToken() || $client->isAccessTokenExpired()) {
            return $this->redirect($this->generateUrl('video_authenticate_google'));
        }

        $listVideos = $my_client->getClientVideos($youtube);
        $video = new Video();
        $form = $this->createFormBuilder($video)
                ->add('latitude', 'hidden')
                ->add('longitude', 'hidden')
                ->add('youtube_id', 'choice', array(
                    'choices'   => $listVideos
                    )
                )
                ->add('name')
                ->add('description', 'textarea')
                ->add('save','submit')
                ->getForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $video->setUser($this->getUser());
            $em->persist($video);
            $em->flush();

            return $this->redirect($this->generateUrl('video_tagged'));
        }
        $this->get('session')->set('token', $client->getAccessToken());

        return $this->render('KonaniVideoBundle:Default:newTag.html.twig', array(
                'form' => $form->createView(),
            ));
    }
}
