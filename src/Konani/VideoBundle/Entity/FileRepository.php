<?php

namespace Konani\VideoBundle\Entity;

use Doctrine\ORM\EntityRepository;


class FileRepository extends EntityRepository
{
    public function findOneByIdAndUserId($id, $userId)
    {
        $query = $this->getEntityManager()
            ->createQueryBuilder('f')
            ->where('f.id = :id AND f.userId = :user_id')
            ->setParameter('id', $id)
            ->setParameter('user_id', $userId)
            ->setMaxResults(1)
            ->getQuery();

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }
}
