<?php

namespace AppBundle\Service;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\UnitOfWork;

class EntityService
{
    /** @const */
    const SERVICE_NAME = 'entity.service';

    /** @var  Registry */
    protected $doctrine;

    /**
     * @param Registry $doctrine
     */
    public function setDoctrine($doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function isPropertyUpdated($propertyName, $entity)
    {
        /** @var UnitOfWork $uow */
        $uow = $this->doctrine->getManager()->getUnitOfWork();
        $uow->computeChangeSets();

        if ($uow->isScheduledForUpdate($entity)) {
            $changeSet = $uow->getEntityChangeSet($entity);
            return isset($changeSet[$propertyName]);
        }

        return false;
    }

    public function getEntityMetadata($entity)
    {
        return $this->doctrine->getManager()->getClassMetadata(get_class($entity));
    }

    public function getPropertyOriginalValue($propertyName, $entity)
    {
        /** @var UnitOfWork $uow */
        $uow = $this->doctrine->getManager()->getUnitOfWork();
        $uow->computeChangeSets();

        if ($uow->isScheduledForUpdate($entity)) {
            $changeSet = $uow->getEntityChangeSet($entity);
            if (isset($changeSet[$propertyName])) {
                return $changeSet[$propertyName][0];
            }
        }
        return null;
    }
}
