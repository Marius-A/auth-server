<?php

namespace AppBundle\Event\Listener;

use AppBundle\Entity\Listener\UserListener;
use AppBundle\Entity\User;
use AppBundle\Event\Interfaces\RemoveEntityInterface;
use AppBundle\Service\FlushedEntityManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

class DoctrineListener
{
    /** @var  UserListener */
    protected $userListener;
    
    /** @var  FlushedEntityManager */
    protected $flushedEntityManager;

    /**
     * @param UserListener $userListener
     */
    public function setUserListener($userListener)
    {
        $this->userListener = $userListener;
    }

    /**
     * @param FlushedEntityManager $flushedEntityManager
     */
    public function setFlushedEntityManager($flushedEntityManager)
    {
        $this->flushedEntityManager = $flushedEntityManager;
    }

    /**
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        /** @var EntityManager $em */
        $em = $args->getEntityManager();
        foreach ($em->getUnitOfWork()->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof RemoveEntityInterface) {
                $entity->remove();
                $em->persist($entity);
            }
        }
        $em->getUnitOfWork()->computeChangeSets();
    }

    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        $flush = false;
        foreach ($this->flushedEntityManager->getEntityUpdates() as $entity) {
            if ($entity instanceof User) {
                # CREATE IDENTIFIERS FOR MAIN PROVIDER
                $this->userListener->updateMainProviderIdentifiers($entity);
                $flush = true;
            }
        }

        $this->flushedEntityManager->resetEntityUpdates();
        if ($flush) {
            $args->getEntityManager()->flush();
        }
    }
}
