<?php

namespace AppBundle\Service;

use AppBundle\Entity\UserAuthProviderIdentifier;
use AppBundle\Repository\RoleRepository;
use AppBundle\Repository\UserAuthProviderIdentifierRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;

class UserAuthProviderIdentifierService
{
    /** @var Registry */
    protected $doctrine;

    /**
     * @param Registry $doctrine
     */
    public function setDoctrine($doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param UserAuthProviderIdentifier $userAuthProviderIdentifier
     * @return UserAuthProviderIdentifier
     */
    public function save(UserAuthProviderIdentifier $userAuthProviderIdentifier)
    {
        /** @var EntityManager $manager */
        $manager = $this->doctrine->getManager();
        $manager->beginTransaction();

        $this->doctrine->getManager()->persist($userAuthProviderIdentifier);
        $this->doctrine->getManager()->flush();

        $manager->commit();

        return $userAuthProviderIdentifier;
    }
}