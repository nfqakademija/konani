<?php

namespace Konani\VideoBundle\Controller;

use Doctrine\ORM\NoResultException;
use Konani\VideoBundle\Entity\Video;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Google_Service_YouTube;

use Google_Service_Exception;
use Google_Exception;

use Konani\VideoBundle\Entity\File;

use Symfony\Component\HttpFoundation\Request;

/**
 * Controls videos actions - add new, edit, delete, upload to server, upload to youtube...
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
            ->add('description', 'textarea')
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

    public function showAction($id)
    {
        $my_client = $this->get('google_client');
        $client = $my_client->getGoogleClient();
        $youtube = new Google_Service_YouTube($client);
        try {
            $video = $this->getDoctrine()
                ->getRepository('KonaniVideoBundle:Video')
                ->findOneBy(array(
                        'id' => $id
                    ));
            $videoResponse = $youtube->videos->listVideos('snippet,statistics', array(
                    'id' => $video->getYoutubeId(),
                ));
        } catch (NoResultException $e) {
            throw $this->createNotFoundException(
                'No video found for id '.$id
            );
        }
        return $this->render('KonaniVideoBundle:Default:show.html.twig', array(
                'items' => $videoResponse->getItems(),
                'coordinates' => $video,
            ));
    }
    /**
     * Lists all user uploaded locally videos
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function uploadedAction()
    {
        return $this->render('KonaniVideoBundle:Default:uploaded.html.twig', array(
                'uploadedVideos' => $this->getUser()->getFiles()
            ));
    }

    /**
     * Lists all user tagged videos
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function taggedAction()
    {
        return $this->render('KonaniVideoBundle:Default:tagged.html.twig', array(
                'taggedVideos' => $this->getUser()->getVideos()
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
                'No uploaded video found for id '.$id
            );
        }
        $em->remove($file);
        $em->flush();
        return $this->redirect($this->generateUrl('video_uploaded'));
    }

    public function deleteTaggedAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        try {
            $video = $this->getDoctrine()
                ->getRepository('KonaniVideoBundle:Video')
                ->find(['id'=>$id, 'userId'=>$this->getUser()->getId()]);
        } catch (NoResultException $e) {
            throw $this->createNotFoundException(
                'No tagged video found for id '.$id
            );
        }
        $em->remove($video);
        $em->flush();
        return $this->redirect($this->generateUrl('video_tagged'));
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
        $state = $this->get('request')->get('state');
        if ($code) {
            $my_client->authenticateToken($code, $state);
            return $this->redirect($this->generateUrl('video_authenticate_google'));
        }
        $my_client->resetToken();
        if (!$client->getAccessToken() || $client->isAccessTokenExpired()) {
            return $this->render('KonaniVideoBundle:Default:authenticateGoogle.html.twig',['authUrl' => $my_client->getAuthUrl()]);
        }
        $youtube = new Google_Service_YouTube($client);
        try {
            $channelLinked = $my_client->getChannelLinked($youtube);
        } catch (Google_Service_Exception $e) {
            throw $this->createAccessDeniedException("A service error occurred: ".htmlspecialchars($e->getMessage()));
        } catch (Google_Exception $e) {
            throw $this->createAccessDeniedException("An client error occurred: ".htmlspecialchars($e->getMessage()));
        }
        $this->get('session')->set('token', $client->getAccessToken());
        return $this->render('KonaniVideoBundle:Default:authenticateGoogle.html.twig',['channelLinked' => $channelLinked]);
    }

    /**
     * Uploads video to clients youtube channel
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function uploadToYoutubeAction($id)
    {
        $my_client = $this->get('google_client');
        $client = $my_client->getGoogleClient();
        $my_client->resetToken();
        $file = $this->getDoctrine()->getRepository('KonaniVideoBundle:File')->find($id);
        if (!$file) {
            throw $this->createNotFoundException('No video found for id ' . $id);
        }
        $youtube = new Google_Service_YouTube($client);
        try {
            if (!$client->getAccessToken() || $client->isAccessTokenExpired() || !$my_client->getChannelLinked($youtube)) {
                return $this->redirect($this->generateUrl('video_authenticate_google'));
            } else {
                $my_client->uploadVideo($file, $youtube);
                $this->get('session')->set('token', $client->getAccessToken());
                return $this->redirect($this->generateUrl('video_delete_uploaded', ['id' => $id]));
            }
        } catch (Google_Service_Exception $e) {
            throw $this->createAccessDeniedException("A service error occurred: ".htmlspecialchars($e->getMessage()));
        } catch (Google_Exception $e) {
            throw $this->createAccessDeniedException("An client error occurred: ".htmlspecialchars($e->getMessage()));
        }
    }

    public function newTagAction(Request $request)
    {
        //$ip = $this->get('request')->getClientIp();
        $ip = "86.38.9.252";
        $my_client = $this->get('google_client');
        $client = $my_client->getGoogleClient();
        $my_client->resetToken();
        if (!$client->getAccessToken() || $client->isAccessTokenExpired()) {
            return $this->redirect($this->generateUrl('video_authenticate_google'));
        }
        $youtube = new Google_Service_YouTube($client);
        try {
            $listVideos = $my_client->getClientVideos($youtube);
        } catch (Google_Service_Exception $e) {
            throw $this->createAccessDeniedException("A service error occurred: ".htmlspecialchars($e->getMessage()));
        } catch (Google_Exception $e) {
            throw $this->createAccessDeniedException("An client error occurred: ".htmlspecialchars($e->getMessage()));
        }
        $video = new Video();
        $form = $this->createFormBuilder($video, array(
                'attr'=> array(
                    'id' => 'newTag'
                )
            ))
            ->add('latitude', 'hidden')
            ->add('longitude', 'hidden')
            ->add('youtube_id', 'choice', array(
                'choices'   => $listVideos
                )
            )
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
                'location' => $this->get('location')->getMyLocation($ip),
            ));
    }
}
