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
        $my_client = $this->get('google_client');
        $client = $my_client->getGoogleClient();
        $youtube = new Google_Service_YouTube($client);
        try {
            $listCategories = $my_client->getVideoCategories($youtube);
        } catch (Google_Service_Exception $e) {
            throw $this->createAccessDeniedException("A service error occurred: ".htmlspecialchars($e->getMessage()));
        } catch (Google_Exception $e) {
            throw $this->createAccessDeniedException("An client error occurred: ".htmlspecialchars($e->getMessage()));
        }
        $file = new File();
        $form = $this->createFormBuilder($file)
            ->add('name')
            ->add('description', 'textarea')
            ->add('category','choice',array(
                    'choices' => $listCategories
                ))
            ->add('tags', 'text', array(
                    'attr' => array(
                        'data-role'=> 'tagsinput'
                    )
                ))
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
     * Shows video player and map
     *
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
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
     * Deletes entities from specified repository
     *
     * @param $repo
     * @param $search
     * @return bool
     */
    public function deleteUserEntityAction($repo, $id)
    {
        $em = $this->getDoctrine()->getManager();
        try {
            $entity = $this->getDoctrine()
                ->getRepository($repo)
                ->find(array(
                        'id' => $id,
                        'userId' => $this->getUser()->getId()
                    ));
        } catch (NoResultException $e) {
            throw $this->createNotFoundException(
                'No entities found for with given parameters'
            );
        }
        $em->remove($entity);
        $em->flush();
        return true;
    }

    /**
     * Deletes video entity if exists
     *
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteUploadedAction($id)
    {
        $this->deleteUserEntityAction('KonaniVideoBundle:File',$id);
        $this->get('session')->getFlashBag()->add('success', 'Uploaded video successfully deleted.');
        return $this->redirect($this->generateUrl('video_uploaded'));
    }

    /**
     * Deletes video geotag
     *
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteTaggedAction($id)
    {
        $this->deleteUserEntityAction('KonaniVideoBundle:Video',$id);
        $this->get('session')->getFlashBag()->add('success', 'Map tag for video successfully deleted.');
        return $this->redirect($this->generateUrl('video_tagged'));
    }

    /**
     * Authenticates client and redirects if gets right parametes / otherwise provides authentication link
     *
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
     *
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
                $this->deleteUserEntityAction('KonaniVideoBundle:File',$id);
                $this->get('session')->getFlashBag()->add('success', sprintf('Video "%s" successfully uploaded to Youtube.',$file->getName()));
                return $this->redirect($this->generateUrl('video_uploaded'));
            }
        } catch (Google_Service_Exception $e) {
            throw $this->createAccessDeniedException("A service error occurred: ".htmlspecialchars($e->getMessage()));
        } catch (Google_Exception $e) {
            throw $this->createAccessDeniedException("An client error occurred: ".htmlspecialchars($e->getMessage()));
        }
    }

    /**
     * Map and form for a new video tag
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newTagAction(Request $request)
    {
        $ip = $this->get('request')->server->get('HTTP_X_REAL_IP');
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
