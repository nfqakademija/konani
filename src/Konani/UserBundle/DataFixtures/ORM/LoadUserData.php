<?php

namespace Konani\UserBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Konani\UserBundle\Entity\User;

/**
 * Class for generating demo users
 */
class LoadUserData implements FixtureInterface{

    /**
     * Function for persisting a demo user to database
     */
    public function load(ObjectManager $manager)
    {
        $user = new User();
        $user->setName('test');
        $user->setSurename('test');
        $user->setUsername('test');
        $user->setEmail('test@test.lt');
        $user->setPlainPassword('test');
        $user->setEnabled(true);

        $manager->persist($user);
        $manager->flush();
    }
} 