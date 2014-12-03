<?php

namespace Konani\VideoBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * VideoRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class VideoRepository extends EntityRepository
{
    public function findVideosByCoordinates($lat1, $lat2, $lng1, $lng2)
    {
        $qb = $this->createQueryBuilder('v');

        $qb->where('v.latitude >= :lat1 ')
            ->andWhere('v.latitude <= :lat2')
            ->andWhere('v.longitude >= :lng1')
            ->andWhere('v.longitude <= :lng2')
            ->setParameter('lat1', $lat1)
            ->setParameter('lat2', $lat2)
            ->setParameter('lng1', $lng1)
            ->setParameter('lng2', $lng2)
        ;
        return $qb->getQuery()->getResult();
    }
}
