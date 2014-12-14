<?php

namespace Konani\VideoBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Konani\VideoBundle\Entity\Video;

/**
 * Class for generating demo users
 */
class LoadVideoData extends AbstractFixture implements OrderedFixtureInterface {
    /**
     * Function for persisting a demo videos to database
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $video1 = new Video();
        $video1->setYoutubeId('_f7lh1_UgZI');
        $video1->setLatitude('46.52556396560762');
        $video1->setLongitude('10.16261100769043');
        $video1->setUser($this->getReference('user'));
        $manager->persist($video1);
        $video2 = new Video();
        $video2->setYoutubeId('0_cQOp_AI2o');
        $video2->setLatitude('36.532467485977534');
        $video2->setLongitude('31.982574462890625');
        $video2->setUser($this->getReference('user'));
        $manager->persist($video2);
        $video3 = new Video();
        $video3->setYoutubeId('SMwzA2NX-jY');
        $video3->setLatitude('55.28850055792437');
        $video3->setLongitude('21.15966796875');
        $video3->setUser($this->getReference('user'));
        $manager->persist($video3);
        $video4 = new Video();
        $video4->setYoutubeId('_bIqQlxKTTM');
        $video4->setLatitude('55.27818031002363');
        $video4->setLongitude('24.665422439575195');
        $video4->setUser($this->getReference('user'));
        $manager->persist($video4);
        $video5 = new Video();
        $video5->setYoutubeId('LsBGFU1Skv0');
        $video5->setLatitude('54.81625404334729');
        $video5->setLongitude('24.459375143051147');
        $video5->setUser($this->getReference('user'));
        $manager->persist($video5);
        $video6 = new Video();
        $video6->setYoutubeId('wYk955JBn0I');
        $video6->setLatitude('54.81631586290487');
        $video6->setLongitude('24.458699226379395');
        $video6->setUser($this->getReference('user'));
        $manager->persist($video6);
        $manager->flush();
    }
    public function getOrder()
    {
        return 2; // the order in which fixtures will be loaded
    }
} 