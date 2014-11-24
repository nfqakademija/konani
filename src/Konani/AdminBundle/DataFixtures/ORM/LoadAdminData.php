<?php

namespace Konani\UserBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Konani\AdminBundle\Entity\Admin;

/**
 * Class for generating demo admins
 */
class LoadAdminData implements FixtureInterface{

    /**
     * Function for persisting a demo admin to database
     */
    public function load(ObjectManager $manager)
    {
        $admin = new Admin();
        $admin->setName('admin');
        $admin->setSurename('admin');
        $admin->setUsername('admin');
        $admin->setEmail('admin@admin.lt');
        $admin->setPlainPassword('admin');
        $admin->setIsActive(true);

        $manager->persist($admin);
        $manager->flush();
    }
} 