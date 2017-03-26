<?php

namespace AppBundle\Repository;

use AppBundle\Entity\AuthProvider;
use AppBundle\Entity\UserAuthProviderIdentifier;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class UserAuthProviderIdentifierRepository extends EntityRepository
{
    /**
     * @param $identifier
     * @param AuthProvider $authProvider
     * @return UserAuthProviderIdentifier
     */
    public function findOneByAuthProvider($identifier, AuthProvider $authProvider)
    {
        /** @var EntityManager $manager */
        $manager = $this->getEntityManager();

        /** @var QueryBuilder $qb */
        $qb = $this->createQueryBuilder('uapi');

        $qb->select('uapi');
        $qb->innerJoin('AppBundle:UserAuthProvider', 'uap', 'WITH', 'uapi.userAuthProvider = uap');
        $qb->where('uapi.identifier = :identifier');
        $qb->andWhere('uap.authProvider = :authProvider');
        $qb->setMaxResults(1);
        $qb->setFirstResult(0);

        $qb->setParameter('identifier', $identifier);
        $qb->setParameter('authProvider', $authProvider);

        $query = $qb->getQuery();
        $userAuthProviderIdentifier = $query->getResult();
        $userAuthProviderIdentifier = array_pop($userAuthProviderIdentifier);

        return $userAuthProviderIdentifier;
    }
}