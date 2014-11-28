<?php

namespace Konani\VideoBundle\Entity;

use Doctrine\ORM\EntityRepository;


class FileRepository extends EntityRepository
{
    public function getOneByIdAndUserId($id, $userId)
    {
        $qb = $this->createQueryBuilder('f');

        $qb->where('f.id = :id ')
            ->andWhere('f.userId = :user_id')
            ->setParameter('id', $id)
            ->setParameter('user_id', $userId)
            ->setMaxResults(1)
            ;

        return $qb->getQuery()->getSingleResult();
    }
}
