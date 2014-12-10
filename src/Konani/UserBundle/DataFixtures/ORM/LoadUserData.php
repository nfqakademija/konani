<?php

namespace Konani\UserBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Konani\UserBundle\Entity\User;

/**
 * Class for generating demo users
 */
class LoadUserData extends AbstractFixture implements OrderedFixtureInterface {

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

        $this->addReference('user', $user);
    }
    public function getOrder()
    {
        return 1; // the order in which fixtures will be loaded
    }
} 